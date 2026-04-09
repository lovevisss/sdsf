<template>
  <Head title="谈心谈话总览" />
  <AppLayout>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          谈心谈话总览
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          查看您的谈心谈话工作统计信息和趋势分析
        </p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Count -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.753 2 16.5S6.5 26.747 12 26.747s10-4.5 10-10.247S17.5 6.253 12 6.253z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  年度谈话次数
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ stats.yearlyCount }}
                </dd>
              </dl>
            </div>
          </div>
        </div>

        <!-- Pending Appointments -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  待确认约谈
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ stats.pendingAppointments }}
                </dd>
              </dl>
            </div>
          </div>
        </div>

        <!-- Pending Records -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  待登记谈话
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ stats.pendingRecords }}
                </dd>
              </dl>
            </div>
          </div>
        </div>

        <!-- Top Topic -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  最多主题
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white truncate">
                  {{ stats.topTopic }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Topic Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            谈话主题分布
          </h3>
          <div class="space-y-3">
            <div v-for="item in charts.topicDistribution" :key="item.topic" class="flex items-center">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-24">
                {{ item.topic }}
              </span>
              <div class="ml-3 flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  class="bg-blue-600 h-2 rounded-full"
                  :style="{ width: calculatePercentage(item.count, charts.topicDistribution) + '%' }"
                />
              </div>
              <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ item.count }}
              </span>
            </div>
          </div>
        </div>

        <!-- Method Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            谈话方式分布
          </h3>
          <div class="space-y-3">
            <div v-for="item in charts.methodDistribution" :key="item.conversation_method" class="flex items-center">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-32">
                {{ formatMethod(item.conversation_method) }}
              </span>
              <div class="ml-3 flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  class="bg-green-600 h-2 rounded-full"
                  :style="{ width: calculatePercentage(item.count, charts.methodDistribution) + '%' }"
                />
              </div>
              <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ item.count }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Monthly Trend -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          近一年谈话趋势
        </h3>
        <div class="flex items-end justify-between h-64 gap-2">
          <div
            v-for="item in charts.monthlyTrend"
            :key="item.month"
            class="flex-1 flex flex-col items-center"
          >
            <div
              class="w-full bg-blue-600 rounded-t"
              :style="{ height: calculatePercentage(item.count, charts.monthlyTrend) + '%' }"
            />
            <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">
              {{ item.month }}月
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mt-8 flex flex-wrap gap-4">
        <Link
          v-if="$page.props.auth.user.role === 'advisor'"
          href="/conversations/appointments"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500"
        >
          管理约谈
        </Link>
        <Link
          v-if="$page.props.auth.user.role === 'advisor'"
          href="/conversations/records/create"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 dark:hover:bg-green-500"
        >
          记录谈话
        </Link>
        <Link
          href="/conversations/records"
          class="inline-flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          查看历史记录
        </Link>
      </div>
    </div>
  </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  stats: Object,
  charts: Object,
})

const formatMethod = (method) => {
  const methods = {
    'one_on_one': '一对一谈话',
    'one_on_many': '一对多谈话',
    'dorm_visit': '走访寝室',
    'class_meeting': '主题班会',
    'family_contact': '家校联系',
  }
  return methods[method] || method
}

const calculatePercentage = (value, array) => {
  const max = Math.max(...array.map(item => item.count))
  return (value / max) * 100
}
</script>

