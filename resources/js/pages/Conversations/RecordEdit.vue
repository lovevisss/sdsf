<template>
  <Head title="编辑谈话记录" />
  <AppLayout>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          编辑谈话记录
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          修改谈话记录信息
        </p>
      </div>

      <!-- Form -->
      <Form
        :action="`/conversations/records/${record.id}`"
        method="patch"
        #default="{ errors, processing }"
        class="bg-white dark:bg-gray-800 shadow rounded-lg p-6"
      >
        <div class="space-y-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Conversation Form -->
            <div>
              <label class="block text-sm font-medium text-gray-900 dark:text-white">
                谈话形式 *
              </label>
              <select
                :value="record.conversation_form"
                name="conversation_form"
                required
                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="talk">谈心</option>
                <option value="consultation">咨询</option>
                <option value="sport">运动</option>
                <option value="meal">用餐</option>
                <option value="tea_break">下午茶</option>
                <option value="seminar">研讨</option>
                <option value="other">其他</option>
              </select>
              <p v-if="errors.conversation_form" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.conversation_form }}
              </p>
            </div>

            <!-- Conversation Method -->
            <div>
              <label class="block text-sm font-medium text-gray-900 dark:text-white">
                谈话方式 *
              </label>
              <select
                :value="record.conversation_method"
                name="conversation_method"
                required
                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="one_on_one">一对一谈话</option>
                <option value="one_on_many">一对多谈话</option>
                <option value="dorm_visit">走访寝室</option>
                <option value="class_meeting">主题班会</option>
                <option value="family_contact">家校联系</option>
              </select>
              <p v-if="errors.conversation_method" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.conversation_method }}
              </p>
            </div>
          </div>

          <!-- Conversation Reason -->
          <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white">
              谈话原因 *
            </label>
            <select
              :value="record.conversation_reason"
              name="conversation_reason"
              required
              class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="academic">学业</option>
              <option value="life">生活</option>
              <option value="psychology">心理</option>
              <option value="discipline">纪律</option>
              <option value="other">其他</option>
            </select>
            <p v-if="errors.conversation_reason" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.conversation_reason }}
            </p>
          </div>

          <!-- Topic -->
          <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white">
              谈话主题 *
            </label>
            <input
              :value="record.topic"
              type="text"
              name="topic"
              required
              class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500"
            />
            <p v-if="errors.topic" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.topic }}
            </p>
          </div>

          <!-- Conversation Content -->
          <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white">
              谈话内容
            </label>
            <textarea
              :value="record.content"
              name="content"
              rows="6"
              class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500"
            />
            <p v-if="errors.content" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.content }}
            </p>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Conversation Time -->
            <div>
              <label class="block text-sm font-medium text-gray-900 dark:text-white">
                谈话时间 *
              </label>
              <input
                :value="formatDateTime(record.conversation_at)"
                type="datetime-local"
                name="conversation_at"
                required
                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
              />
              <p v-if="errors.conversation_at" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.conversation_at }}
              </p>
            </div>

            <!-- Location -->
            <div>
              <label class="block text-sm font-medium text-gray-900 dark:text-white">
                谈话地点
              </label>
              <input
                :value="record.location"
                type="text"
                name="location"
                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500"
              />
              <p v-if="errors.location" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.location }}
              </p>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex gap-4 pt-6">
            <button
              type="submit"
              :disabled="processing"
              class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500 disabled:opacity-50"
            >
              {{ processing ? '保存中...' : '保存更改' }}
            </button>
            <Link
              :href="`/conversations/records/${record.id}`"
              class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              取消
            </Link>
          </div>
        </div>
      </Form>
    </div>
  </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Form, Link } from '@inertiajs/vue3'

defineProps({
  record: Object,
  classes: Array,
})

const formatDateTime = (date) => {
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}
</script>

