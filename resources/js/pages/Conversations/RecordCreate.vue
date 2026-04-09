<template>
  <Head title="谈话记录登记" />
  <AppLayout>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          谈话记录登记
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          填写谈话详情，完成电子化留痕
        </p>
      </div>

      <!-- Form -->
      <Form
        action="/conversations/records"
        method="post"
        #default="{ errors, processing }"
        class="bg-white dark:bg-gray-800 shadow rounded-lg p-6"
      >
        <div class="space-y-6">
          <!-- Student Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white">
              学生 *
            </label>
            <input
              v-model="form.student_id"
              type="number"
              name="student_id"
              required
              class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500"
            />
            <p v-if="errors.student_id" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.student_id }}
            </p>
          </div>

          <!-- Class Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white">
              班级 *
            </label>
            <select
              v-model="form.class_model_id"
              required
              class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">请选择班级</option>
              <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                {{ cls.department.name }} - {{ cls.name }}
              </option>
            </select>
            <p v-if="errors.class_model_id" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors.class_model_id }}
            </p>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Conversation Form -->
            <div>
              <label class="block text-sm font-medium text-gray-900 dark:text-white">
                谈话形式 *
              </label>
              <select
                v-model="form.conversation_form"
                required
                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">请选择</option>
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
                v-model="form.conversation_method"
                required
                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">请选择</option>
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
              v-model="form.conversation_reason"
              required
              class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">请选择</option>
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
              v-model="form.topic"
              type="text"
              name="topic"
              required
              placeholder="请输入谈话主题"
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
              v-model="form.content"
              name="content"
              rows="6"
              placeholder="详细记录谈话内容..."
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
                v-model="form.conversation_at"
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
                v-model="form.location"
                type="text"
                name="location"
                placeholder="例如：办公室、学生宿舍等"
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
              {{ processing ? '保存中...' : '保存记录' }}
            </button>
            <Link
              href="/conversations/records"
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
import { reactive } from 'vue'

defineProps({
  classes: Array,
})

const form = reactive({
  student_id: '',
  class_model_id: '',
  conversation_form: '',
  conversation_method: '',
  conversation_reason: '',
  topic: '',
  content: '',
  conversation_at: '',
  location: '',
})
</script>

