<template>
  <Head title="师德档案详情" />
  <AppLayout>
    <div class="px-4 py-8 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex items-center justify-between">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">师德档案详情</h1>
          <Link href="/ethics/profiles" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">返回列表</Link>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">思想政治素养（25分）</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.summary.modules.political }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">教育教学行为（25分）</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.summary.modules.education }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">学术诚信（25分）</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.summary.modules.academic }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">为人师表（25分）</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.summary.modules.professional }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">年度总分（100分）</p>
            <p class="mt-2 text-2xl font-semibold text-blue-600 dark:text-blue-400">{{ props.summary.totalScore }}</p>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">基础信息</h2>
          <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <p class="text-sm text-gray-700 dark:text-gray-300">工号：{{ props.profile.staff_no }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">姓名：{{ props.profile.name ?? '-' }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">部门：{{ props.profile.unit_name ?? '-' }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">年份：{{ props.summary.year }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">思政年度扣分：{{ props.summary.politicalAnnualDeductionTotal }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">教育教学年度扣分：{{ props.summary.educationAnnualDeductionTotal }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">学术科研年度扣分：{{ props.summary.academicAnnualDeductionTotal }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">为人师表年度扣分：{{ props.summary.professionalAnnualDeductionTotal }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">教师评价平均分：{{ props.summary.teacherEvaluationAverage }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">教师评价后10%触发次数：{{ props.summary.automaticLowEvaluationCount }}</p>
          </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <div class="border-b border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">
            历年信息（按年份倒序）
          </div>
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">年份</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">思政扣分</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">教育教学扣分</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">学术科研扣分</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">为人师表扣分</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">教师评价均分</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">后10%触发次数</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">年度总分</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="props.yearlySummaries.length === 0">
                <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">暂无历年数据</td>
              </tr>
              <tr v-for="item in props.yearlySummaries" :key="item.year">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ item.year }}</td>
                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">{{ item.politicalAnnualDeductionTotal }}</td>
                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">{{ item.educationAnnualDeductionTotal }}</td>
                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">{{ item.academicAnnualDeductionTotal }}</td>
                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">{{ item.professionalAnnualDeductionTotal }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.teacherEvaluationAverage }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.automaticLowEvaluationCount }}</td>
                <td class="px-4 py-3 text-sm text-blue-600 dark:text-blue-400">{{ item.totalScore }}</td>
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
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps<{
  profile: {
    staff_no: string
    name?: string | null
    unit_name?: string | null
  }
  summary: {
    year: number
    politicalAnnualDeductionTotal: number
    politicalAnnualRemainingScore: number
    educationAnnualDeductionTotal: number
    educationAnnualRemainingScore: number
    academicAnnualDeductionTotal: number
    academicAnnualRemainingScore: number
    professionalAnnualDeductionTotal: number
    professionalAnnualRemainingScore: number
    teacherEvaluationAverage: number
    automaticLowEvaluationCount: number
    modules: {
      political: number
      education: number
      academic: number
      professional: number
    }
    totalScore: number
  }
  yearlySummaries: Array<{
    year: number
    politicalAnnualDeductionTotal: number
    educationAnnualDeductionTotal: number
    academicAnnualDeductionTotal: number
    professionalAnnualDeductionTotal: number
    teacherEvaluationAverage: number
    automaticLowEvaluationCount: number
    totalScore: number
  }>
}>()
</script>

