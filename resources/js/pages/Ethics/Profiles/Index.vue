<template>
  <Head title="师德一人一档" />
  <AppLayout>
    <div class="px-4 py-8 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">师德一人一档</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">档案可用于职称评聘、评优评先、岗位聘用等关键环节。</p>
          </div>
          <Link href="/ethics/dashboard" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">返回治理中心</Link>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <select
              v-model="department"
              class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
            >
              <option value="">全部部门</option>
              <option v-for="item in props.departmentOptions" :key="item.code" :value="item.code">{{ item.name }}</option>
            </select>
            <button
              type="button"
              class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
              @click="applyDepartmentFilter"
            >
              筛选
            </button>
            <button
              type="button"
              class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
              @click="clearDepartmentFilter"
            >
              重置
            </button>
          </div>
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">工号</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">姓名</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">单位</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="props.staffRecords.data.length === 0">
                <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">暂无档案数据</td>
              </tr>
              <tr v-for="item in props.staffRecords.data" :key="item.staff_no" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ item.staff_no }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                  <Link :href="item.profile_url" class="text-blue-600 hover:underline dark:text-blue-400">{{ item.name }}</Link>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.unit_name ?? '-' }}</td>
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
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

type ProfileRow = {
  staff_no: string
  name?: string
  unit_name?: string
  profile_url: string
}

type DepartmentOption = {
  code: string
  name: string
}

const props = defineProps<{
  staffRecords: {
    data: ProfileRow[]
  }
  departmentFilter?: string
  departmentOptions: DepartmentOption[]
}>()

const department = ref(props.departmentFilter ?? '')

const applyDepartmentFilter = (): void => {
  router.get('/ethics/profiles', { department: department.value || undefined }, { preserveState: true })
}

const clearDepartmentFilter = (): void => {
  department.value = ''
  router.get('/ethics/profiles', {}, { preserveState: false })
}
</script>

