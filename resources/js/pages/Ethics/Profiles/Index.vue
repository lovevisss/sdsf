<template>
  <Head title="师德一人一档" />
  <AppLayout>
    <div class="px-4 py-8 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">师德一人一档</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">按年度展示五维度分值、总分和档案明细入口。</p>
          </div>
          <Link href="/ethics/dashboard" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">返回治理中心</Link>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <select v-model="department" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
              <option value="">全部部门</option>
              <option v-for="item in props.departmentOptions" :key="item.code" :value="item.code">{{ item.name }}</option>
            </select>
            <input v-model="name" type="text" placeholder="按姓名搜索" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
            <button type="button" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" @click="applyDepartmentFilter">筛选</button>
            <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700" @click="clearDepartmentFilter">重置</button>
            <a href="/ethics/reports/export?type=profile_details" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">导出明细</a>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">工号</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">姓名</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">单位</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">年度</th>
                  <th class="score-head">政治</th>
                  <th class="score-head">教育</th>
                  <th class="score-head">学术</th>
                  <th class="score-head">师表</th>
                  <th class="score-head">纪律</th>
                  <th class="score-head">总分</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-if="props.staffRecords.data.length === 0">
                  <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">暂无档案数据</td>
                </tr>
                <tr v-for="item in props.staffRecords.data" :key="item.staff_no" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ item.staff_no }}</td>
                  <td class="px-4 py-3 text-sm">
                    <Link :href="item.profile_url" class="text-blue-600 hover:underline dark:text-blue-400">{{ item.name ?? '-' }}</Link>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.unit_name ?? '-' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.latest_year }}</td>
                  <td class="score-cell" :class="scoreClass(item.latest_scores?.political)">{{ item.latest_scores?.political ?? 20 }}</td>
                  <td class="score-cell" :class="scoreClass(item.latest_scores?.education)">{{ item.latest_scores?.education ?? 20 }}</td>
                  <td class="score-cell" :class="scoreClass(item.latest_scores?.academic)">{{ item.latest_scores?.academic ?? 20 }}</td>
                  <td class="score-cell" :class="scoreClass(item.latest_scores?.professional)">{{ item.latest_scores?.professional ?? 20 }}</td>
                  <td class="score-cell" :class="scoreClass(item.latest_scores?.discipline)">{{ item.latest_scores?.discipline ?? 20 }}</td>
                  <td class="score-cell font-semibold text-blue-600 dark:text-blue-400">{{ totalScore(item) }}</td>
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
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

type Scores = {
  political: number
  education: number
  academic: number
  professional: number
  discipline?: number
}

type ProfileRow = {
  staff_no: string
  name?: string
  unit_name?: string
  profile_url: string
  latest_year?: number
  latest_scores?: Scores
}

type DepartmentOption = {
  code: string
  name: string
}

const props = defineProps<{
  staffRecords: { data: ProfileRow[] }
  departmentFilter?: string
  nameFilter?: string
  departmentOptions: DepartmentOption[]
}>()

const department = ref(props.departmentFilter ?? '')
const name = ref(props.nameFilter ?? '')

const applyDepartmentFilter = (): void => {
  router.get('/ethics/profiles', {
    department: department.value || undefined,
    name: name.value || undefined,
  }, { preserveState: true })
}

const clearDepartmentFilter = (): void => {
  department.value = ''
  name.value = ''
  router.get('/ethics/profiles', {}, { preserveState: false })
}

const scoreClass = (score?: number): string => {
  const value = score ?? 20

  if (value <= 0) {
    return 'font-semibold text-red-600 dark:text-red-400'
  }

  if (value <= 10) {
    return 'font-semibold text-yellow-600 dark:text-yellow-400'
  }

  return 'text-gray-700 dark:text-gray-300'
}

const totalScore = (item: ProfileRow): number => {
  const scores = item.latest_scores

  if (!scores) {
    return 100
  }

  return ['political', 'education', 'academic', 'professional', 'discipline']
    .map((key) => scores[key as keyof Scores] ?? 20)
    .reduce((sum, value) => sum + value, 0)
}
</script>

<style scoped>
.score-head {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: rgb(107 114 128);
}

.score-cell {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
}
</style>
