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
            <p class="text-sm text-gray-500 dark:text-gray-400">近十次考核记录</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.summary.assessmentCount }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">未销号预警</p>
            <p class="mt-2 text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ props.summary.openWarningCount }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">近十次投诉记录</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ props.summary.caseCount }}</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">思政素养{{ props.summary.year }}年扣分</p>
            <p class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">{{ props.summary.politicalAnnualDeductionTotal }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">剩余{{ props.summary.politicalAnnualRemainingScore }} / 25</p>
          </div>
          <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">教育教学{{ props.summary.year }}年扣分</p>
            <p class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">{{ props.summary.educationAnnualDeductionTotal }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">剩余{{ props.summary.educationAnnualRemainingScore }} / 25</p>
          </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">基础信息</h2>
          <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <p class="text-sm text-gray-700 dark:text-gray-300">姓名：{{ props.profile.user?.name }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">邮箱：{{ props.profile.user?.email }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">角色：{{ props.profile.user?.role }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">部门：{{ props.profile.department?.name ?? '-' }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">岗位：{{ props.profile.position ?? '-' }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">身份类型：{{ props.profile.identity_type ?? '-' }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">入职时间：{{ formatDate(props.profile.hired_at) }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">档案状态：{{ props.profile.status }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">预警记录</h3>
            <div class="mt-4 space-y-3">
              <div v-if="props.profile.warnings.length === 0" class="text-sm text-gray-500 dark:text-gray-400">暂无预警记录</div>
              <div v-for="item in props.profile.warnings" :key="item.id" class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ item.warning_level }} / {{ item.source_type }} / {{ item.status }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ item.reason }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">投诉记录</h3>
            <div class="mt-4 space-y-3">
              <div v-if="props.profile.cases.length === 0" class="text-sm text-gray-500 dark:text-gray-400">暂无投诉记录</div>
              <div v-for="item in props.profile.cases" :key="item.id" class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ item.title }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ item.status }} / {{ item.risk_level }}</p>
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

const props = defineProps<{
  profile: Record<string, any>
  summary: {
    year: number
    assessmentCount: number
    openWarningCount: number
    caseCount: number
    politicalAnnualDeductionTotal: number
    politicalAnnualRemainingScore: number
    educationAnnualDeductionTotal: number
    educationAnnualRemainingScore: number
  }
}>()

const formatDate = (value: unknown): string => {
  if (!value) {
    return '-'
  }

  return new Date(value as string | number | Date).toLocaleDateString('zh-CN')
}
</script>

