<template>
  <Head title="投诉举报与闭环处置" />
  <AppLayout>
    <div class="px-4 py-8 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">投诉举报与闭环处置</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">支持提交、受理、核查、处置、反馈、归档全流程留痕。</p>
          </div>
          <Link href="/ethics/dashboard" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">返回治理中心</Link>
        </div>

        <form class="grid grid-cols-1 gap-3 rounded-lg bg-white p-5 shadow md:grid-cols-2 lg:grid-cols-4 dark:bg-gray-800" @submit.prevent="submitCase">
          <input v-model="form.title" type="text" placeholder="问题标题" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
          <select v-model="form.channel" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            <option value="pc">PC端</option>
            <option value="mobile">移动端</option>
            <option value="wechat">公众号</option>
            <option value="wecom">企业微信</option>
            <option value="other">其他</option>
          </select>
          <select v-model="form.risk_level" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            <option value="low">低风险</option>
            <option value="medium">中风险</option>
            <option value="high">高风险</option>
          </select>
          <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input v-model="form.is_anonymous" type="checkbox" /> 匿名举报
          </label>
          <textarea v-model="form.content" rows="3" class="rounded-md border border-gray-300 px-3 py-2 text-sm md:col-span-2 lg:col-span-3 dark:border-gray-600 dark:bg-gray-900 dark:text-white" placeholder="问题描述" />
          <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" :disabled="form.processing">{{ form.processing ? '提交中...' : '提交举报' }}</button>
        </form>

        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">标题</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">对象</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">状态</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">风险</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">上报时间</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="props.cases.data.length === 0">
                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">暂无数据</td>
              </tr>
              <tr v-for="item in props.cases.data" :key="item.id">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ item.title }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.profile?.user?.name ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.status }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ item.risk_level }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ formatDate(item.reported_at) }}</td>
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
import { Head, Link, useForm } from '@inertiajs/vue3'

type CaseRow = {
  id: number | string
  title?: string
  status?: string
  risk_level?: string
  reported_at?: string | null
  profile?: { user?: { name?: string } }
}

const props = defineProps<{
  cases: {
    data: CaseRow[]
  }
  currentStatus?: string | null
}>()

const form = useForm({
  ethics_profile_id: null,
  department_id: null,
  channel: 'pc',
  is_anonymous: false,
  title: '',
  content: '',
  risk_level: 'low',
})

const submitCase = () => {
  form.post('/ethics/cases', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('title', 'content', 'risk_level', 'is_anonymous')
    },
  })
}

const formatDate = (value: unknown): string => {
  if (!value) {
    return '-'
  }

  return new Date(value as string | number | Date).toLocaleString('zh-CN')
}
</script>

