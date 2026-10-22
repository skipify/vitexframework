# Vitex 框架 SQL 注入修复测试报告

- 日期：2026-10-20
- 分支：`2.0`（本仓库）、`1.x`（`fix-sql-inject-1.x` 分支，基于 `origin/1.x`）
- 数据库：MySQL 8.2.0 @127.0.0.1:3306，`chuangqi` 库（真实业务库）
- 用法样本：`bagcms5/apps/chuangqi/model`（59 个模型）与 `bagcms5/apps/admin/model`（14 个模型）

## 一、修复内容

### 1. where 查询彻底 prepare 化（两分支）

| 位置 | 修改 |
|---|---|
| 2.0 `Model/Wrapper/Holder/SqlUtil` | 删除子查询内联替换；`whereTuple/orWhereTuple/whereIn/whereNotIn/whereExists系列/union` 接收子查询对象时占位参数重写 key 后合并；`Holder::mergeData` 整词正则改写（防 `:id` 误伤 `:id_1`）；合并参数独立存储，`reset()` 不清除（保证 `page()` 的 count 重建可用）；构建子查询前重置其构建期参数（修复对象复用） |
| 1.x `vitex/ext/Model.php` | 新增 `_bindParams` 容器：条件存入 `_sql` 时即生成 `:w_N` 占位符（生命周期与条件一致）；`buildWhere` 全部内联引号逻辑移除；`whereTuple/union/whereIn(对象)` 合并子查询参数（`mw_` 前缀防连环替换）；执行点（`update/delete/stepField/_get/_getAll/count/max/min/sum/avg`）统一传参，`page()` 恢复参数快照 |

### 2. 操作符白名单

- `where/orWhere/having/join` 共用白名单：`= != <> > < >= <= like`（`<>` 为新增支持）
- `orderBy` 方向白名单：`ASC/DESC`
- 1.x `increment/decrement` 数值必须为数字；两分支 `limit/offset` 强转 int

### 3. 标识符防护（白名单为主，分级）

- **列名严格白名单**（where 系列条件列、orderBy、groupBy、having 键、insert/update 字段名）：仅允许 `字母/数字/下划线/中划线` 及点号分隔的 `table.column` 形式（`^[A-Za-z0-9_\-]+(\.[A-Za-z0-9_\-]+)*$`）。空格、制表符、换行等一切不可见字符、括号、反引号、星号均拒绝 —— 不支持函数与表达式（orderBy/groupBy 经真实代码扫描确认无函数用法）。表达式需求请使用 `whereRaw` 等显式原生方法
- **select/field/distinct 字段**（需要 `count()`、算术表达式等函数能力）：保持黑名单 guard —— 拒绝注释符（`--` `/*` `#`）、分号、子查询关键字（`select/union/delete/update/insert/drop`）、时间盲注函数（`sleep/benchmark/extractvalue/updatexml/load_file/outfile/dumpfile`）、盲注函数（`if/case/ascii/ord/substr/substring/mid/char/elt`）、「反引号+任意空白」
- **join 的 ON 列**：因存在复合条件真实用法（如 `leftJoin('t b', '(a.memberid', '=', 'b.memberid and a.orgid=b.orgid')`），保持黑名单 guard
- **表名**（from/join 表）：允许携带 `select` 子查询（文档化用法，如 `MarticleNew::rightJoin("(select ...) as me")`），仅拦截注释符与分号
- **having 值**：2.0 懒绑定（COUNT 构建跳过 HAVING 时不产生多余参数）；1.x 直接绑定
- **whereIn/whereNotIn 字符串子查询**：按列名级 guard 拦截（含 select 即拒），子查询统一走 `Model::sub()` 对象 API

### 4. 升级到严格白名单的兼容性说明

- bagcms5（chuangqi + admin 全部模型）经静态扫描：where/orderBy/groupBy/having/join 列**全部**符合白名单（纯列名 + 点号），51 用例覆盖矩阵与基线零差异
- 唯一例外：`McacheDetail.php:161` 的 `where('(`update_at`+`ttl`)', '<', $time)` 为表达式列 —— 需迁移为 `whereRaw('(`update_at`+`ttl`) < ' . (int)$time)`
- 预包裹反引号（`` `id` ``）在列名参数中不再支持（框架自动包裹）

### 13. 最终全项目交叉审计（bagcms5 全部 10 个应用）

扫描范围从 chuangqi/admin 扩大到 **bagcms5 全项目**（admin/article/chuangqi/cli/front/open/osps/search/service/site/stat，osps 55 模型、全项目 1123+1983 处 where 等），新发现并修复 3 处真实用法破坏：

| 真实用法 | 出处 | 问题 | 修复 |
|---|---|---|---|
| `having('sum(pv)','>',2000)` | stat/Mstat.php:910 | having 键被严格白名单拦截 | having 键降为表达式级校验（允许聚合函数） |
| `where('(UNIX_TIMESTAMP(CAST(SYSDATE()AS DATE))-isolation_at)/(24*60*60) - isolated_days','>',1)` 等表达式列 | service/epidemic、school | where 列被严格白名单拦截；2.0 占位符 key 含空格（cleanColumn 未剥空白）导致绑定失败 | where 列降为黑名单级（允许普通函数、仍拦子查询/盲注/注释）；cleanColumn 剥离全部非占位符字符 |
| `select('(CASE WHEN m.orgid=227 THEN ... END)')` | stat/Mstat.php | CASE 被 select 路径黑名单误拦 | 黑名单分级：**select/distinct 字段**允许 CASE WHEN（SELECT_DANGER_PATTERN，不含盲注函数族）；where/having/join/orderBy 等其余场景保持完整黑名单 |

分级结果：`orderBy/groupBy/insert·update键` 维持严格白名单（全项目扫描确认纯列名）；`where列/having键` 黑名单（完整）；`select/distinct` 黑名单（不含盲注函数族）。

交叉验证（51 用例 × 4 版本 + osps/stat/epidemic 7 用例 × 2 分支）：两分支新增模式 9/9 一致（5 真实 + 4 攻击拦截）；防线回归 strict 10/10、raw 15/15、sqlite 62+51、覆盖矩阵与基线一致，跨分支差异仍为 6 项历史既有（无新增漂移）。

## 二、测试方法

1. **基线对比**：同一测试脚本分别运行于修复前（HEAD / origin/1.x）与修复后代码，每轮运行前重置数据，逐用例 diff
2. **覆盖矩阵**：从两个 model 目录 grep 全部 Model API 调用（1123 处 where / 384 from / 179 select / 100 leftJoin / 92 whereIn / 59 page / 22 increment 等），提炼 51 个用例
3. **对抗注入**：LEAKED_PASSWORD 金丝雀表探测数据泄露、行数变化探测布尔盲注、SQL 文本检查占位符

## 三、覆盖矩阵结果（51 用例 × 新旧对比）

### 2.0 分支：48/51 完全一致，3 处差异均为修复旧代码固有 bug

| 用例 | 旧代码 | 新代码 | 说明 |
|---|---|---|---|
| B09 orWhereTuple | SQL 语法错误 `OR ( WHERE cateid=102)` | 正常返回 2 行 | 旧代码不剥离 WHERE 关键字，该 API 从未可用 |
| B12 whereExists(字符串) | `Param::__construct` TypeError | 正常返回 6 行 | 旧代码该 API 从未可用（文档承诺的用法） |
| F08 sub 对象复用 | 第二次构建生成 `101_1` 错误值 → SQL 报错 | 两次均正常 | 旧代码内联替换与 key 自增冲突 |

### 1.x 分支：50/51 完全一致，1 处有意语义变化

| 用例 | 旧代码 | 新代码 | 说明 |
|---|---|---|---|
| B02 whereIn 传字符串 `'1,2'` | 无引号内联为 SQL 片段 `in (1,2)` → 2 行 | 作为单个值绑定 `in ('1,2')` → 1 行 | 旧行为是注入隐患路径；新行为语义更正确，**传多值请使用数组** |

### 用例清单（真实项目模式对应）

- **A 基础**：where 全操作符 / where 数组 / orWhere 链 / get(主键) / getBy·getAllBy 魔术方法 / select 逗号串+别名+数组+表达式+`u.*`（Mtopnav、Msnapshot、Muser 模式）/ limit / orderBy 多字段连缀与默认值
- **B 条件**：whereIn 数组与字符串 / whereNotIn / whereNull·NotNull / whereBetween（MarticleNew publish_at_time）/ findInSet（Mtopnav）/ whereTuple / orWhereTuple / whereIn 子对象 / whereRaw 带变量（Morg、Mcate 模式）/ whereExists
- **C 聚合**：count / max·min·sum·avg / groupBy+聚合别名+orderBy（Msearchlog 热词）/ having / pluck / field
- **D join**：三表 leftJoin（Muser）/ innerJoin / join+page（Msnapshot）/ rightJoin 子查询表名（MarticleNew）
- **E 写操作**：insert 单行·多行 / update（Muser）/ delete / increment·decrement 单·多字段（MscoreItem）/ sort 循环更新（admin Model）/ 事务 commit·rollBack（MliveNew）
- **F 复合**：union 子对象 / from 子查询 / page 常规·+join·+having·+tuple / setJustSql / sub 对象复用 / ORM 属性读写

## 四、防注入对抗审计（严格白名单实施后）

- **列名攻击向量**（tab/反引号/子查询/sleep/if 盲注/预包裹，共 10 项 × 2 分支）：全部被白名单拦截
- **合法形态**（点列 `a.age`、下划线开头、orderBy 聚合别名 `num`、groupBy 点列、having 纯列，共 6 项 × 2 分支）：全部通过

- **值注入**（where/like/whereTuple/whereIn 子对象/having/between/findInSet/insert/update 值）：全部占位符绑定，payload 无法改变 SQL 结构
- **操作符/方向/数值注入**（where·having·join op、orderBy way、increment 数值、limit）：全部白名单/强转拦截
- **标识符注入**（select/orderBy/groupBy/field/distinct/where 列/having 键/insert·update 键携带子查询或注释）：全部拦截（列名级 guard）
- 回归验证：2.0 sqlite 62/62、1.x sqlite 51/51 通过

### 5. 三次审查补充（白名单收口与死代码清理）

- **increment/decrement 列**纳入严格白名单（实测 `increment("pv`=pv+1,name`='hacked", 1)`、`increment("pv, sleep(3)", 1)` 均拦截）
- **聚合函数字段**（count/max/min/sum/avg）与 **join ON 列**、select/field/distinct 保持黑名单 guard（`select` 子查询、`sleep`/`if` 等实测拦截，`count(id) as num` 等合法用法通过）
- **死代码清理（2.0）**：`Model::$isSub` 属性及赋值（内联方案遗留）、`buildWhere` 中已无写入的 `wheretuple` 遗留循环与模板键、`SqlUtil::formatVal`（旧引号内联助手）、`WrapperConst::EXISTS/NOT_EXISTS`（exists 改为直接拼接后无引用）、`Holder::$holdName/getHoldName/setHoldName`（无引用）
- **死代码清理（1.x）**：`_sql['on']` 遗留键

### 6. 原生口子收口（残余风险修复）

新增 `guardSqlFragment`（分层校验）接入全部原生入口，**对既有用法零影响**（bagcms 全部原生用法经扫描不含分号/注释/危险函数）：

| 入口 | 校验级别 | 拦截 | 放行 |
|---|---|---|---|
| `whereRaw` | strict | light 全部 + `select/union/delete/update/insert/drop`（子查询关键字） | `find_in_set`、复合条件（bagcms 全部 6 处 whereRaw 用法实测通过）；Play.php 的 `({$sql})` 形态以 `(` 开头走原有静默丢弃路径，不受影响 |
| `union(字符串)`、`query`、`execute`、`fetch`、`fetchAll`、`whereExists(字符串)` | light | 同上（堆叠语句/时间盲注/文件读写/信息泄露全部拦截） | 完整单条 SQL |
| `setRaw` | strict | light 全部 + `select/union/delete/update/insert/drop`（SET 表达式无需子查询） | `col + 1` 等表达式（increment 内部路径验证通过） |
| from/join 表名 | light+ | 危险函数族 + 注释/分号 | select 子查询（MarticleNew 用法验证通过） |

实测证据：`whereRaw('and 1=1; drop table x')` 在 guard 未生效时**真实 DROP 了测试表**（堆叠注入实证），接入 guard 后拦截；全部 12 项攻击向量（两分支 ×12）拦截、10 项合法用法通过。

### 7. 五轮审查补充（whereRaw 严格化 + Pdo 层兜底）

- **whereRaw 升级 strict**（拦子查询关键字）：bagcms 全部 whereRaw 用法经扫描无 select；2.0 的 `({$sql})` 形态因 `(` 前缀走静默丢弃不受影响（实测）；实测 `whereRaw('and name in (select pw from secret)')` 拦截。1.x 注意：1.x 无静默丢弃逻辑，若子查询片段直接传入 whereRaw 现会抛异常（bagcms 1.x 用法无此形态）
- **修订（实战误拦反馈）：whereRaw 条件片段放行合法读子查询**。线上业务存在 `whereRaw(' (userid = 1 and isread=0) or (userid=0 and id not in (select messageid from ...))')` 形态（kudo Mmessage::unread），strict 模式整段拦截产生 Fatal。新增 `guardRawCondition`（3.0 `SqlUtil::guardRawCondition`、1.x `Model::guardRawCondition`）：剥离引号字符串字面量后检测子查询关键字，含 select 等关键字的片段按 `guardSubQuery` 规则放行（仍拦截注释符/堆叠语句/时间盲注与锁函数/盲注与编码外泄函数族/逻辑位运算符，仅放行子查询关键字，信任级别与 from/join 表名的 select 子查询一致）；无子查询关键字的片段维持 strict 不变。适用范围：3.0 `Wrapper::sql()`（whereRaw/whereTuple）与 `Wrapper::having()`、1.x `whereRaw` 入口与 `buildWhere` 构建期复检（whereraw/wheretuple）；`setRaw`（UpdateWrapper）维持 strict（SET 表达式无需子查询）。实测：kudo 原始片段、`in (select ...)`、`exists (select ...)`、having 子查询全部放行；堆叠 `;drop`、`sleep(3)`、子查询内 `sleep/hex/if(substr(...))`、`extractvalue`、`||`/`&&`、`handler`、反引号函数名全部拦截。注意：`whereRaw('and name in (select pw from secret)')` 自本轮起放行（与 `not in (select ...)` 文本同构无法区分），whereRaw 参数仍禁止接收用户输入（见「六、保留的原生口子」）
- **修订（连带修复）：3.0/2.0 `whereRaw` 不再静默丢弃无前缀片段**。原实现对非 `and`/`or` 前缀的片段（如 `(a=1) or (b=2)`、以 `(` 开头的完整条件）静默丢弃 —— 本轮线上案例片段即以 `(` 开头，部署拷贝透传才暴露 guard 报错；同步官方代码后该条件会被无声丢失导致查询结果错误。改为：`and`/`or` 前缀整词匹配（`/^and\b|^or\b/i`，顺带修复 `order by ...`/`android ...` 被 `or`/`and` 前缀误截断的隐性 bug 及 `or` 分支 `substr(,3)` 多吃一字符的问题），无前缀非空片段按当前连接模式原样透传（与 1.x 行为一致），空片段仍丢弃。实测：用户片段进入 WHERE、and/or 兼容写法不变、`order by id desc` 完整透传、`or(x='x')` 正确剥离前缀、堆叠/盲注仍拦截
- **Pdo 层兜底 guard**（2.0 `service/model/Pdo.php`、1.x `ext/Pdo.php` 的 `query/execute`）：拦截注释/分号堆叠/时间文件/信息泄露函数 —— 覆盖 `$model->DB->query()` 直连绕过 Model 层校验的路径（实测堆叠与盲注拦截、正常 SQL 与框架构建 SQL 全部通过，含 MliveRed 的 `DB->execute($this->sql, $data)` 模式）
- **死代码终扫**：2.0 `formatColumn` 仅剩 join 复合条件调用（有意保留）；`SqlUtil::wrapColumn` 剩余调用均为聚合字段 guard/内部表达式路径（有意保留）；前四轮清理项零残留

### 8. 六轮审查补充（绕过收口）

- **反引号包裹函数名绕过**：MySQL 支持 `` `sleep`(3) `` 形式的函数调用，函数形式黑名单 `\bsleep\s*\(` 被反引号打断 —— 实测 `` whereRaw('and `sleep`(3)') `` 延迟 3 秒、`` query('select `version`()') `` 泄露版本号。修复：黑名单全部改为**词形式**（`\bsleep\b` 等，反引号内同样命中）。代价：raw SQL 中引用名为 sleep/version 等的列会被拦截（bagcms 无此用法，可接受）
- **直连 `getSelectWrapper()->sql()` 绕过 whereRaw guard**：实测注入 `1=1 or \`sleep\`(2)` 片段进入 SQL（靠 Pdo 兜底拦截）。修复：`Wrapper::sql()` 入口加 strict guard（whereTuple 等内部片段为列名与占位符，实测不受影响）
- **`setSqlSet()` 整体替换 `_sql` 绕过全部入口校验**：实测注入 `whereraw`/`union` 危险片段。修复：setSqlSet 对 raw 字符串字段（1.x whereraw/wheretuple strict、union light；2.0 union light）校验

### 9. 七轮审查补充（setSqlSet 结构化字段注入 + 构建期防线）

- **setSqlSet 注入结构化字段**：1.x 的 where/having/select/order/limit 条目、2.0 的 join 条目存于 `_sql`，`setSqlSet()` 整体替换可绕过入口校验 —— 实测 1.x `setSqlSet(['select' => ['(select pw from secret) as name']])` **真实泄露 LEAKED**，where/having/limit 注入靠 Pdo 兜底拦截（缺入口防线）。2.0 的 select/order/limit 走 wrapper 存储，setSqlSet 注入无效（确认非漏洞面）
- **修复：新增 `guardBuiltIdentifier`/`guardBuiltValue` 构建期防线**（guardIdentifier 之外的第二道闸）：
  - 完整反引号包裹形式（含点号/别名/星号）为安全字面标识符放行；括号**配平**的函数/聚合表达式（`max(\`id\`) as info`）按词表轻量校验；其余（不配平括号、反引号+空白、黑名单词）拒绝
  - 条件值必须为占位符形式（`:w_N`），否则按原生片段校验
  - 1.x 覆盖：where 列/op/值、having 键/op/值、join 类型/列/op、select/distinct/group/order 列/way、limit/offset 数值、whereraw/wheretuple；2.0 覆盖：join 类型/列/op
- 实测 5 项注入向量（两分支）全部在构建期拦截，51 用例覆盖矩阵零差异（含聚合别名、join 复合条件等合法形式）

### 10. 八轮审查补充（表达式盲注收口 + 冗余清理）

- **配平表达式分支的盲注函数缺口**：`guardBuiltIdentifier` 的括号配平分支此前仅查 RAW_DANGER 词表（缺 `if/case/ascii/substr` 等盲注函数族）—— 实测 setSqlSet 注入 `order by if(pw='S1',id,-id)` 盲注生效、`select ascii(substring(pw,1,1))` **实锋试提取数据**（83='S'）。修复：新增 `guardExpression`（与 guardIdentifier 同款完整黑名单、不含反引号+空白规则）用于配平分支，合法聚合表达式 `max(\`id\`) as info` 不受影响
- **union 构建期防线**：两分支 buildSql 的 union 循环增加 light 校验（此前仅入口与 Pdo 兜底）
- **冗余清理**：setSqlSet 入口校验移除（构建期防线已全覆盖 whereraw/wheretuple/union）；2.0 whereRaw 内联校验移除（由 Wrapper::sql() 入口统一执行）

### 11. 九轮审查补充（词表统一重构）

- **词表正则统一**：黑名单正则此前在 guardIdentifier/guardExpression/RAW_DANGER/1.x Pdo 四处重复维护（第八轮的 if/case 缺口正是失同步所致）。重构为**组合式常量**：`RE_TERMINATORS`（注释/分号）+ `RE_DANGER_FUNCS`（时间/文件/信息函数）+ `RE_SUBQUERY`（子查询关键字）+ `RE_BLIND_FUNCS`（盲注函数族），按场景组合：原生 SQL 用 TERMINATORS+DANGER_FUNCS（select 等由严格模式追加）；标识符/表达式场景追加 SUBQUERY+BLIND_FUNCS。1.x Pdo 直接引用 Model 的公开常量，全库单一事实来源
- **Wrapper::having() 原始口子**补 guard（严格模式，此前可直连绕过 Model::having）
- 设计取舍说明：`if()/case` 等盲注函数仅在**标识符/表达式**场景拦截（列名/orderBy/select 字段中出现即恶意），`query()/execute()` 等完整 SQL 入口不拦（开发者写 `select if(a,b,c)` 属合法），维持既有兼容

### 12. 十轮审查补充（直连 wrapper 入口收口）

- **Wrapper::orderBy 的 way 参数**：Model 层有白名单但直连 \getSelectWrapper()->orderBy() 绕过 —— 实测 "asc; drop table" 注入片段进入 SQL(靠 Pdo 兜底拦截)。修复：Wrapper 层入口白名单
- **Wrapper::exists/notExists**：直连注入同样仅靠 Pdo 兜底 —— 补 light guard(子查询合法)
- 死代码终扫：全部 guard/helper(wrapStrictColumn/guardBuiltIdentifier/guardSqlFragment 等共 10 项)均有真实调用，历轮清理项零残留；isSubQuery 在用(勿与已删除的 isSub 属性混淆)

**固有能力边界**：from/join 表名的 select 子查询与合法用法（`rightJoin("(select ...) as me")`）文本同构，结构性攻击（堆叠/盲注/文件/信息泄露/注释逃逸）已全部封死，"结构相同的读子查询"在不破坏既有用法的前提下无法进一步区分 —— 表名参数仍应避免接收用户输入。

## 五、行为变化与兼容性声明

1. `getSql()/setJustSql()` 输出的 SQL 中，条件值为占位符（`:w_N` 等）而非内联字面量；自行执行该 SQL 需配合绑定参数（`debugData` 可取参数）
2. `whereIn` 传字符串现在作为**单个值**绑定（旧 1.x 作为 SQL 片段内联）；多值请传数组
3. `whereExists` 系列在两分支均可用了（旧 2.0 直接 TypeError、旧 1.x 参数错位）
4. `orWhereTuple`（2.0）修复为可用（旧版生成非法 SQL）
5. 同一 `Model::sub()` 对象可多次复用（旧版第二次数值错位/报错）
6. having 的值参数化：与列名比较的旧写法（`having('a','=','b')` 中 `b` 是列引用）语义变化为字符串比较

## 六、保留的原生口子（与 whereRaw 同级信任，禁止接收用户输入）

| 口子 | 说明 |
|---|---|
| `whereRaw()` | 原样拼接 WHERE 片段 |
| `union(字符串)` / `query()` / `fetch()` / `fetchAll()` / `execute()` | 原生 SQL |
| `setRaw()`（2.0） | 原样 SET 片段 |
| **from / join 的表名参数** | 支持 `select` 子查询（文档化用法），仅拦注释符与分号；表名不得接收用户输入 |
| 列名/表名参数整体 | 框架无法区分「开发者写的函数表达式」与「攻击者构造的片段」，业务层把用户输入传入列名参数时需自行白名单 |

## 七、测试复现

测试脚本与数据（本地临时目录，随会话清理）：

- `cover_test.php` —— 51 用例覆盖矩阵（参数：框架根 + 分支标记）
- `audit20.php` / `audit1x.php` —— 对抗注入矩阵（LEAKED 金丝雀）
- `exectest.php` / `bctest.php` / `edge.php` / `test1x.php` —— sqlite 全量回归
- `reset_data.sql` —— xt_article 测试数据重置（uuid 前缀 `vitex_t`，测试后已清理）
