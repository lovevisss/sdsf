<template>
  <Head title="师德档案详情" />
  <AppLayout>
    <div class="px-4 py-8 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">师德档案详情</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">{{ props.profile.staff_no }} - {{ props.profile.name ?? '-' }} - {{ props.profile.unit_name ?? '-' }}</p>
          </div>
          <Link href="/ethics/profiles" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">返回列表</Link>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
          <ScoreCard label="政治素养" :score="props.summary.modules.political" />
          <ScoreCard label="教育教学" :score="props.summary.modules.education" />
          <ScoreCard label="学术诚信" :score="props.summary.modules.academic" />
          <ScoreCard label="为人师表" :score="props.summary.modules.professional" />
          <ScoreCard label="工作纪律" :score="props.summary.modules.discipline" />
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">年度总分</p>
            <p class="mt-2 text-2xl font-semibold text-blue-600 dark:text-blue-400">{{ props.summary.totalScore }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ warningLabel(props.summary.warningLevel) }}</p>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">基础信息</h2>
          <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <Info label="工号" :value="props.profile.staff_no" />
            <Info label="姓名" :value="props.profile.name ?? '-'" />
            <Info label="部门" :value="props.profile.unit_name ?? '-'" />
            <Info label="年度" :value="String(props.summary.year)" />
          </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <div class="border-b border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">历年汇总</div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th class="table-head">年度</th>
                  <th class="table-head">政治扣分</th>
                  <th class="table-head">教育扣分</th>
                  <th class="table-head">学术扣分</th>
                  <th class="table-head">师表扣分</th>
                  <th class="table-head">纪律扣分</th>
                  <th class="table-head">总分</th>
                  <th class="table-head">预警</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-if="props.yearlySummaries.length === 0">
                  <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">暂无历年数据</td>
                </tr>
                <tr v-for="item in props.yearlySummaries" :key="item.year">
                  <td class="table-cell text-gray-900 dark:text-white">{{ item.year }}</td>
                  <td class="table-cell text-red-600 dark:text-red-400">{{ item.politicalAnnualDeductionTotal }}</td>
                  <td class="table-cell text-red-600 dark:text-red-400">{{ item.educationAnnualDeductionTotal }}</td>
                  <td class="table-cell text-red-600 dark:text-red-400">{{ item.academicAnnualDeductionTotal }}</td>
                  <td class="table-cell text-red-600 dark:text-red-400">{{ item.professionalAnnualDeductionTotal }}</td>
                  <td class="table-cell text-red-600 dark:text-red-400">{{ item.disciplineAnnualDeductionTotal ?? 0 }}</td>
                  <td class="table-cell text-blue-600 dark:text-blue-400">{{ item.totalScore }}</td>
                  <td class="table-cell text-gray-700 dark:text-gray-300">{{ warningLabel(item.warningLevel) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <div class="border-b border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">扣分明细</div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th class="table-head">时间</th>
                  <th class="table-head">维度</th>
                  <th class="table-head">违规类型</th>
                  <th class="table-head">扣分</th>
                  <th class="table-head">备注</th>
                  <th class="table-head">登记人</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-if="props.deductionRecords.length === 0">
                  <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">暂无扣分明细</td>
                </tr>
                <tr v-for="(item, index) in props.deductionRecords" :key="`${item.module_key}-${item.violation_at}-${index}`">
                  <td class="table-cell text-gray-700 dark:text-gray-300">{{ formatDate(item.violation_at) }}</td>
                  <td class="table-cell text-gray-700 dark:text-gray-300">{{ item.module }}</td>
                  <td class="table-cell text-gray-700 dark:text-gray-300">{{ item.violation_type }}. {{ item.violation_type_label }}</td>
                  <td class="table-cell font-semibold text-red-600 dark:text-red-400">-{{ item.deduction_points }}</td>
                  <td class="table-cell text-gray-700 dark:text-gray-300">{{ item.notes ?? '-' }}</td>
                  <td class="table-cell text-gray-700 dark:text-gray-300">{{ item.recorder_name ?? '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { defineComponent, h } from 'vue'

type Modules = {
  political: number
  education: number
  academic: number
  professional: number
  discipline: number
}

const props = defineProps<{
  profile: {
    staff_no: string
    name?: string | null
    unit_name?: string | null
  }
  summary: {
    year: number
    modules: Modules
    totalScore: number
    warningLevel?: string | null
  }
  yearlySummaries: Array<{
    year: number
    politicalAnnualDeductionTotal: number
    educationAnnualDeductionTotal: number
    academicAnnualDeductionTotal: number
    professionalAnnualDeductionTotal: number
    disciplineAnnualDeductionTotal?: number
    totalScore: number
    warningLevel?: string | null
  }>
  deductionRecords: Array<{
    module: string
    module_key: string
    violation_type: number
    violation_type_label: string
    violation_at: string
    deduction_points: number
    notes?: string | null
    recorder_name?: string | null
  }>
}>()

const ScoreCard = defineComponent({
  props: {
    label: { type: String, required: true },
    score: { type: Number, required: true },
  },
  setup(cardProps) {
    return () => h('div', { class: 'rounded-lg bg-white p-5 shadow dark:bg-gray-800' }, [
      h('p', { class: 'text-sm text-gray-500 dark:text-gray-400' }, cardProps.label),
      h('p', { class: 'mt-2 text-2xl font-semibold text-gray-900 dark:text-white' }, String(cardProps.score)),
    ])
  },
})

const Info = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: String, required: true },
  },
  setup(infoProps) {
    return () => h('p', { class: 'text-sm text-gray-700 dark:text-gray-300' }, `${infoProps.label}: ${infoProps.value}`)
  },
})

const warningLabel = (level?: string | null): string => {
  return { blue: '蓝色预警', yellow: '黄色预警', red: '红色预警' }[level ?? ''] ?? '无预警'
}

const formatDate = (value: string): string => {
  return new Date(value).toLocaleString('zh-CN')
}
</script>

<style scoped>
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
