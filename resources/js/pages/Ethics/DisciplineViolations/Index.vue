<template>
  <Head title="工作纪律违规录入" />
  <AppLayout>
    <div class="px-4 py-8 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">工作纪律违规录入</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">记录考勤异常、出国境、校外兼职等工作纪律事项，单维满分 20。</p>
          </div>
          <Link href="/ethics/dashboard" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">返回治理中心</Link>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <Metric label="当前人员年度记录数" :value="selectedSummary?.violation_count ?? 0" />
          <Metric label="当前人员年度扣分" :value="selectedSummary?.total_deduction ?? 0" tone="red" />
          <Metric label="当前人员剩余分值" :value="selectedSummary?.remaining_score ?? 20" tone="green" />
        </div>

        <form class="grid grid-cols-1 gap-3 rounded-lg bg-white p-5 shadow md:grid-cols-2 lg:grid-cols-3 dark:bg-gray-800" @submit.prevent="submit">
          <div class="relative">
            <input v-model="staffQuery" type="text" placeholder="搜索工号、姓名或单位" class="field" @focus="showStaffPanel = true" @input="onStaffInput" />
            <div v-if="showStaffPanel" class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900">
              <button v-for="item in filteredStaffOptions" :key="item.staff_no" type="button" class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800" @mousedown.prevent="selectStaff(item)">
                {{ item.staff_no }} - {{ item.name }} - {{ item.unit_name ?? '-' }}
              </button>
              <div v-if="filteredStaffOptions.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">没有匹配人员</div>
            </div>
          </div>

          <select v-model="form.violation_type" class="field">
            <option :value="null">选择违规类型</option>
            <option v-for="(label, key) in props.violationTypeOptions" :key="key" :value="Number(key)">{{ key }}. {{ label }}</option>
          </select>

          <select v-model="form.severity_level" class="field">
            <option value="">选择违规等级</option>
            <option value="A">A级：扣 5 分</option>
            <option value="B">B级：扣 10 分</option>
            <option value="C">C级：扣 20 分</option>
          </select>

          <input v-model="form.violation_at" type="datetime-local" class="field" />
          <input v-model="form.handler_department" type="text" placeholder="经办部门" class="field" />
          <input v-model="form.deduction_basis" type="text" placeholder="扣分依据" class="field" />
          <textarea v-model="form.notes" rows="2" placeholder="备注" class="field md:col-span-2" />

          <button type="submit" :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">
            {{ form.processing ? '提交中...' : '保存记录' }}
          </button>
          <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" @click="clearStaffFilter">查看全部</button>
        </form>

        <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-700 dark:bg-red-950/30 dark:text-red-300">
          <p v-for="(value, key) in form.errors" :key="key">{{ value }}</p>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <div class="border-b border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">人员年度扣分汇总</div>
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="table-head">工号</th>
                <th class="table-head">姓名</th>
                <th class="table-head">单位</th>
                <th class="table-head">扣分</th>
                <th class="table-head">剩余</th>
                <th class="table-head">操作</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="props.staffSummaries.length === 0">
                <td colspan="6" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">暂无汇总数据</td>
              </tr>
              <tr v-for="item in props.staffSummaries" :key="item.staff_no" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700" @click="pickStaffSummary(item)">
                <td class="table-cell text-gray-900 dark:text-white">{{ item.staff_no }}</td>
                <td class="table-cell text-gray-700 dark:text-gray-300">{{ item.staff_name }}</td>
                <td class="table-cell text-gray-700 dark:text-gray-300">{{ item.staff_unit_name ?? '-' }}</td>
                <td class="table-cell text-red-600 dark:text-red-400">{{ item.total_deduction }}</td>
                <td class="table-cell text-green-600 dark:text-green-400">{{ item.remaining_score }}</td>
                <td class="table-cell"><Link :href="item.profile_url" class="text-blue-600 hover:underline dark:text-blue-400" @click.stop>查看档案</Link></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <div class="border-b border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">工作纪律记录明细</div>
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="table-head">人员</th>
                <th class="table-head">类型</th>
                <th class="table-head">等级</th>
                <th class="table-head">时间</th>
                <th class="table-head">扣分</th>
                <th class="table-head">登记人</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="props.records.data.length === 0">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">暂无记录</td>
              </tr>
              <tr v-for="item in props.records.data" :key="item.id">
                <td class="table-cell text-gray-900 dark:text-white">{{ item.staff_no }} - {{ item.staff_name }}</td>
                <td class="table-cell text-gray-700 dark:text-gray-300">{{ typeLabel(item.violation_type) }}</td>
                <td class="table-cell text-gray-700 dark:text-gray-300">{{ item.severity_level ?? '-' }}</td>
                <td class="table-cell text-gray-700 dark:text-gray-300">{{ formatDate(item.violation_at) }}</td>
                <td class="table-cell text-red-600 dark:text-red-400">-{{ item.deduction_points }}</td>
                <td class="table-cell text-gray-700 dark:text-gray-300">{{ item.recorder?.name ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, defineComponent, h, ref } from 'vue'

type ProfileOption = { staff_no: string; name?: string; unit_name?: string }
type ViolationRecord = {
  id: number
  staff_no: string
  staff_name: string
  violation_type: number
  severity_level?: string | null
  violation_at: string
  deduction_points: string | number
  recorder?: { name?: string }
}
type StaffSummary = {
  staff_no: string
  staff_name: string
  staff_unit_name?: string | null
  violation_count: number
  total_deduction: number
  remaining_score: number
  profile_url: string
}

const props = defineProps<{
  records: { data: ViolationRecord[] }
  staffOptions: ProfileOption[]
  selectedStaffNo?: string | null
  selectedStaffSummary?: StaffSummary | null
  staffSummaries: StaffSummary[]
  violationTypeOptions: Record<string, string>
}>()

const Metric = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    tone: { type: String, default: 'default' },
  },
  setup(metricProps) {
    const color = metricProps.tone === 'red' ? 'text-red-600 dark:text-red-400' : metricProps.tone === 'green' ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'
    return () => h('div', { class: 'rounded-lg bg-white p-5 shadow dark:bg-gray-800' }, [
      h('p', { class: 'text-sm text-gray-500 dark:text-gray-400' }, metricProps.label),
      h('p', { class: `mt-2 text-2xl font-semibold ${color}` }, String(metricProps.value)),
    ])
  },
})

const staffQuery = ref('')
const showStaffPanel = ref(false)
const selectedSummary = computed(() => props.selectedStaffSummary ?? null)

const form = useForm({
  staff_no: null as string | null,
  staff_name: '',
  staff_unit_name: '',
  violation_type: null as number | null,
  severity_level: '',
  violation_at: new Date().toISOString().slice(0, 16),
  handler_department: '',
  deduction_basis: '',
  notes: '',
})

const filteredStaffOptions = computed(() => {
  const keyword = staffQuery.value.trim().toLowerCase()
  if (!keyword) return props.staffOptions
  return props.staffOptions.filter((item) => `${item.staff_no} ${item.name ?? ''} ${item.unit_name ?? ''}`.toLowerCase().includes(keyword))
})

const submit = (): void => {
  if (!form.staff_no) {
    form.setError('staff_no', '请先选择违规人员。')
    return
  }

  form.post('/ethics/discipline-violations', { preserveScroll: true })
}

const selectStaff = (item: ProfileOption): void => {
  form.staff_no = item.staff_no
  form.staff_name = item.name ?? ''
  form.staff_unit_name = item.unit_name ?? ''
  staffQuery.value = `${item.staff_no} - ${item.name ?? ''}`
  showStaffPanel.value = false
}

const onStaffInput = (): void => {
  showStaffPanel.value = true
  form.staff_no = null
  form.staff_name = ''
  form.staff_unit_name = ''
}

const pickStaffSummary = (item: StaffSummary): void => {
  form.staff_no = item.staff_no
  form.staff_name = item.staff_name
  form.staff_unit_name = item.staff_unit_name ?? ''
  staffQuery.value = `${item.staff_no} - ${item.staff_name}`
  router.get('/ethics/discipline-violations', { staff_no: item.staff_no }, { preserveState: true })
}

const clearStaffFilter = (): void => {
  router.get('/ethics/discipline-violations', {}, { preserveState: false })
}

const typeLabel = (type: number): string => props.violationTypeOptions[String(type)] ?? `类型${type}`
const formatDate = (value: string): string => new Date(value).toLocaleString('zh-CN')
</script>

<style scoped>
.field {
  width: 100%;
  border-radius: 0.375rem;
  border: 1px solid rgb(209 213 219);
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}

.table-head {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: rgb(107 114 128);
}

.table-cell {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
}
</style>
