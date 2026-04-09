# 谈心谈话系统 (Conversation Management System)

一个完整的高校谈心谈话管理平台，支持教师PC端、教师移动端和学生移动端。

## 系统架构

### 核心功能模块

#### 1. 谈心谈话总览 (Dashboard)
- **URL**: `/conversations/dashboard`
- 显示关键统计数据：
  - 年度谈话次数
  - 待确认约谈数
  - 待登记谈话数
  - 最多主题和方式
- 包含多种图表分析：
  - 谈话主题分布
  - 谈话方式分布
  - 近一年月度趋势
  - TOP 10 学生和班级分布

#### 2. 约谈确认登记 (Appointment Confirmation)
- **URL**: `/conversations/appointments`
- 管理学生的约谈申请
- 支持按状态筛选：待确认、已确认、已完成、已取消
- 快速确认、查看详情、取消操作

#### 3. 谈话记录登记 (Record Registration)
- **URL**: `/conversations/records/create`
- 登记完整的谈话信息：
  - 谈话形式：谈心、咨询、运动、用餐、下午茶、研讨、其他
  - 谈话方式：一对一、一对多、走访寝室、主题班会、家校联系
  - 谈话原因：学业、生活、心理、纪律、其他
  - 主题、内容、时间、地点

#### 4. 谈话历史查询 (Record History)
- **URL**: `/conversations/records`
- 支持多条件搜索：
  - 班级、辅导员、学生学号
  - 谈话主题、方式、日期范围
- 显示为列表和卡片两种模式
- 支持编辑、删除、查看详情

## 数据模型

### 用户角色 (User Roles)
```
- admin: 系统管理员 (查看全校数据)
- leader: 部门/学院领导 (查看部门范围内数据)
- advisor: 辅导员/班主任 (发起和管理约谈)
- student: 学生 (申请约谈)
```

### 核心模型

#### Department (部门)
```php
- id: ID
- name: 部门名称
- code: 部门代码
- description: 描述
- relationships:
  - users: 部门内的用户
  - classes: 部门内的班级
```

#### ClassModel (班级)
```php
- id: ID
- name: 班级名称
- department_id: 所属部门
- grade: 年级 (例如: 2024级)
- description: 描述
- relationships:
  - conversationRecords: 班级的谈话记录
```

#### ConversationAppointment (约谈预约)
```php
- id: ID
- student_id: 申请学生 (FK: users)
- advisor_id: 接收辅导员 (FK: users)
- appointment_type: 预约类型 (talk, consultation, other)
- remarks: 备注
- status: 状态 (pending, confirmed, completed, cancelled)
- appointed_at: 预约时间
- created_at, updated_at: 记录时间
```

#### ConversationRecord (谈话记录)
```php
- id: ID
- advisor_id: 谈话辅导员 (FK: users)
- student_id: 学生 (FK: users)
- class_model_id: 所属班级 (FK: class_models)
- conversation_form: 谈话形式
- conversation_method: 谈话方式
- conversation_reason: 谈话原因
- topic: 主题
- content: 内容
- conversation_at: 谈话时间
- location: 地点
- created_at, updated_at: 记录时间
```

## 安装和使用

### 1. 运行迁移
```bash
php artisan migrate
```

### 2. 生成测试数据
```bash
php artisan db:seed --class=ConversationSystemSeeder
```

### 3. 运行测试
```bash
php artisan test tests/Feature/ConversationSystemTest.php
```

### 4. 启动应用
```bash
php artisan serve
npm run dev
```

## 权限控制 (Authorization)

使用 Laravel Policy 实现细粒度权限控制：

### ConversationRecordPolicy
- **viewAny**: admin, leader, advisor
- **view**: 
  - advisor 可查看自己的记录
  - student 可查看自己的记录
  - leader 可查看部门范围内的记录
  - admin 可查看所有记录
- **create**: advisor 可创建
- **update**: advisor 可更新自己创建的记录
- **delete**: advisor 可删除自己创建的记录

### ConversationAppointmentPolicy
- **viewAny**: admin, advisor
- **view**: 申请人或接收人可查看
- **create**: student 可创建
- **update**: advisor 可更新
- **delete**: 申请人或接收人可删除

## API 路由

### 约谈管理
```
GET    /conversations/appointments              - 列表
POST   /conversations/appointments              - 创建
GET    /conversations/appointments/{id}         - 查看
PATCH  /conversations/appointments/{id}/confirm - 确认
DELETE /conversations/appointments/{id}         - 取消
```

### 谈话记录管理
```
GET    /conversations/records                   - 列表
GET    /conversations/records/create            - 创建表单
POST   /conversations/records                   - 保存
GET    /conversations/records/{id}              - 查看
GET    /conversations/records/{id}/edit         - 编辑表单
PATCH  /conversations/records/{id}              - 更新
DELETE /conversations/records/{id}              - 删除
```

### 仪表板
```
GET    /conversations/dashboard                 - 查看总览
```

## Vue 组件

所有组件位于 `resources/js/Pages/Conversations/` 目录：

- `Dashboard.vue` - 谈心谈话总览
- `AppointmentConfirmation.vue` - 约谈确认登记
- `AppointmentDetail.vue` - 约谈详情
- `RecordCreate.vue` - 记录新增表单
- `RecordHistory.vue` - 历史记录查询
- `RecordDetail.vue` - 记录详情
- `RecordEdit.vue` - 记录编辑表单

## 工厂类 (Factories)

用于生成测试数据：

- `DepartmentFactory` - 生成部门
- `ClassModelFactory` - 生成班级
- `ConversationAppointmentFactory` - 生成约谈预约
- `ConversationRecordFactory` - 生成谈话记录

## 播种器 (Seeders)

`ConversationSystemSeeder` - 一次性生成完整的测试数据集：
- 3 个部门
- 5 个辅导员
- 20 个学生
- 6 个班级
- 50 条谈话记录
- 15 条约谈预约

## 测试覆盖

`ConversationSystemTest` 包含以下测试用例：
- ✅ 辅导员可查看待确认约谈
- ✅ 辅导员可确认约谈
- ✅ 辅导员可创建谈话记录
- ✅ 学生可申请约谈
- ✅ 辅导员可查看仪表板
- ✅ 辅导员可查看谈话记录
- ✅ 辅导员可更新记录
- ✅ 学生无法创建谈话记录（权限检查）

## UI/UX 特性

- ✅ 深色模式支持
- ✅ 响应式设计（移动、平板、桌面）
- ✅ 国际化（中文）
- ✅ 分页支持
- ✅ 实时搜索和筛选
- ✅ 表格和卡片视图切换
- ✅ 确认对话框防止误操作
- ✅ 加载状态指示

## 扩展功能建议

1. **消息通知** - 集成校内消息推送系统
2. **数据导出** - 支持 Excel/PDF 导出
3. **统计分析** - 更细致的数据分析和趋势预测
4. **移动应用** - 原生 iOS/Android 应用
5. **集成认证** - 与学校统一身份认证对接
6. **评估表单** - 谈话评估和反馈机制
7. **跟进任务** - 根据谈话创建后续跟进任务
8. **文件上传** - 支持上传谈话相关文件

## 故障排除

### 数据库迁移失败
- 检查数据库连接配置
- 确保所有外键引用的表已创建
- 使用 `php artisan migrate:refresh --seed` 重新初始化

### 权限被拒绝 403
- 检查用户角色是否正确设置
- 确认用户的 department_id 是否正确设置
- 查看 Policy 类中的授权规则

### 前端组件未加载
- 运行 `npm run dev` 编译 Vue 组件
- 清除浏览器缓存
- 检查网络标签查看是否有错误

## 许可证

本项目遵循 Laravel 开源许可证。

## 联系方式

如有问题，请联系系统管理员。

