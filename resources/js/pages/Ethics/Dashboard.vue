<template>
  <Head title="师德师风治理中心" />
  <AppLayout>
    <div class="px-4 py-8 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl space-y-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">师德师风治理中心</h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">统一查看师德档案、投诉处置和风险预警。</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">师德档案数</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.stats.profileCount }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">未闭环投诉</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.stats.openCaseCount }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">高风险投诉</p>
            <p class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">{{ props.stats.highRiskCaseCount }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">未销号预警</p>
            <p class="mt-2 text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ props.stats.openWarningCount }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800 sm:col-span-2 lg:col-span-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">思想政治素养（25分）年度扣分概况</p>
            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-700 dark:text-gray-300">
              <span>违规记录: <strong>{{ props.stats.politicalViolationCount }}</strong></span>
              <span>当前人员扣分: <strong>{{ props.stats.politicalSelectedDeductionTotal }}</strong></span>
              <span>当前人员剩余: <strong>{{ props.stats.politicalSelectedRemainingScore }}</strong></span>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              年度：{{ props.stats.year }}，当前人员：{{ props.stats.selectedStaffNo ?? '未选择（可在登记页选择后返回）' }}
            </p>
          </div>

          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800 sm:col-span-2 lg:col-span-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">教育教学行为（25分）年度扣分概况</p>
            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-700 dark:text-gray-300">
              <span>违规记录: <strong>{{ props.stats.educationViolationCount }}</strong></span>
              <span>当前人员扣分: <strong>{{ props.stats.educationSelectedDeductionTotal }}</strong></span>
              <span>当前人员剩余: <strong>{{ props.stats.educationSelectedRemainingScore }}</strong></span>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <Link href="/ethics/profiles" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">查看一人一档</Link>
          <Link href="/ethics/cases" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">查看投诉闭环</Link>
          <Link href="/ethics/political-violations" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">手工登记思政违规</Link>
          <Link href="/ethics/education-violations" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">手工登记教育教学违规</Link>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">最近投诉</h2>
            <div class="mt-4 space-y-3">
              <div v-if="props.recentCases.length === 0" class="text-sm text-gray-500 dark:text-gray-400">暂无投诉记录</div>
              <div v-for="item in props.recentCases" :key="item.id" class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                <div class="flex items-center justify-between gap-3">
                  <p class="font-medium text-gray-900 dark:text-white">{{ item.title }}</p>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(item.reported_at) }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">对象：{{ item.profile?.user?.name ?? '未绑定' }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300">状态：{{ item.status }} / 风险：{{ item.risk_level }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">最近预警</h2>
            <div class="mt-4 space-y-3">
              <div v-if="props.recentWarnings.length === 0" class="text-sm text-gray-500 dark:text-gray-400">暂无预警记录</div>
              <div v-for="item in props.recentWarnings" :key="item.id" class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                <div class="flex items-center justify-between gap-3">
                  <p class="font-medium text-gray-900 dark:text-white">{{ item.profile?.user?.name ?? '未绑定' }}</p>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(item.detected_at) }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ item.warning_level }} / {{ item.source_type }} / {{ item.status }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ item.reason }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

type CaseItem = {
  id: number | string
  title?: string
  reported_at?: string | null
  status?: string
  risk_level?: string
  profile?: { user?: { name?: string } }
}

type WarningItem = {
  id: number | string
  detected_at?: string | null
  warning_level?: string
  source_type?: string
  status?: string
  reason?: string
  profile?: { user?: { name?: string } }
}

const props = defineProps<{
  stats: {
    year: number
    selectedStaffNo?: string | null
    profileCount: number
    openCaseCount: number
    highRiskCaseCount: number
    openWarningCount: number
    politicalViolationCount: number
    politicalSelectedDeductionTotal: number
    politicalSelectedRemainingScore: number
    educationViolationCount: number
    educationSelectedDeductionTotal: number
    educationSelectedRemainingScore: number
  }
  recentCases: CaseItem[]
  recentWarnings: WarningItem[]
}>()

const formatDate = (value: unknown): string => {
  if (!value) {
    return '-'
  }

  return new Date(value as string | number | Date).toLocaleString('zh-CN')
}
</script>

