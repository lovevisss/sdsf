<template>
  <Head title="谈话历史查询" />
  <AppLayout>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          谈话历史查询
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          查看和搜索历史谈话记录
        </p>
      </div>

      <!-- Search Filters -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
        <form @submit.prevent="search" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Class Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                班级
              </label>
              <input
                v-model="filters.class_id"
                type="text"
                placeholder="搜索班级..."
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500"
              />
            </div>

            <!-- Topic Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                主题
              </label>
              <input
                v-model="filters.topic"
                type="text"
                placeholder="搜索主题..."
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500"
              />
            </div>

            <!-- Method Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                方式
              </label>
              <select
                v-model="filters.method"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">全部方式</option>
                <option value="one_on_one">一对一谈话</option>
                <option value="one_on_many">一对多谈话</option>
                <option value="dorm_visit">走访寝室</option>
                <option value="class_meeting">主题班会</option>
                <option value="family_contact">家校联系</option>
              </select>
            </div>

            <!-- Date Range -->
            <div>
              <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                日期
              </label>
              <input
                v-model="filters.date_from"
                type="date"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
              />
            </div>
          </div>

          <div class="flex gap-4">
            <button
              type="submit"
              class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500"
            >
              搜索
            </button>
            <button
              type="button"
              @click="clearFilters"
              class="inline-flex items-center px-6 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              清空
            </button>
          </div>
        </form>
      </div>

      <!-- Records Table -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div v-if="records.data.length === 0" class="p-6 text-center">
          <p class="text-gray-600 dark:text-gray-400">
            暂无记录
          </p>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                学生
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                班级
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                主题
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                方式
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                时间
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                操作
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="record in records.data" :key="record.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                {{ record.student.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                {{ record.class_model.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                {{ record.topic }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                {{ formatMethod(record.conversation_method) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                {{ formatDate(record.conversation_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <Link
                  :href="`/conversations/records/${record.id}`"
                  class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-4"
                >
                  查看
                </Link>
                <Link
                  :href="`/conversations/records/${record.id}/edit`"
                  class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 mr-4"
                >
                  编辑
                </Link>
                <button
                  @click="deleteRecord(record)"
                  class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                >
                  删除
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="records.links" class="mt-6 flex justify-center gap-2">
        <template v-for="link in records.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="px-4 py-2 rounded border text-sm font-medium"
            :class="link.active
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
            v-html="link.label"
          />
          <span
            v-else
            class="px-4 py-2 rounded border text-sm font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-500 border-gray-300 dark:border-gray-600 cursor-not-allowed"
            v-html="link.label"
          />
        </template>
      </div>
    </div>
  </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'

defineProps({
  records: Object,
  filters: Object,
})

const filters = reactive({
  class_id: '',
  advisor_id: '',
  topic: '',
  method: '',
  date_from: '',
  date_to: '',
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

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const search = () => {
  router.get('/conversations/records', filters, { preserveState: true })
}

const clearFilters = () => {
  filters.class_id = ''
  filters.advisor_id = ''
  filters.topic = ''
  filters.method = ''
  filters.date_from = ''
  filters.date_to = ''
  router.get('/conversations/records')
}

const deleteRecord = (record) => {
  if (confirm(`确定要删除此记录吗？`)) {
    router.delete(`/conversations/records/${record.id}`, {
      onSuccess: () => {
        router.reload()
      },
    })
  }
}
</script>

