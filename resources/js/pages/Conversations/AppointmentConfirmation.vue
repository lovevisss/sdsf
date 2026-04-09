<template>
  <Head title="约谈确认登记" />
  <AppLayout>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          约谈确认登记
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          确认来自学生的预约申请，状态自动更新
        </p>
      </div>

      <!-- Status Filter -->
      <div class="mb-6 flex gap-4">
        <Link
          :href="`/conversations/appointments?status=pending`"
          :class="{
            'px-4 py-2 rounded-lg font-medium text-white bg-yellow-600 hover:bg-yellow-700': currentStatus === 'pending',
            'px-4 py-2 rounded-lg font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700': currentStatus !== 'pending',
          }"
        >
          待确认 ({{ statusCounts.pending }})
        </Link>
        <Link
          :href="`/conversations/appointments?status=confirmed`"
          :class="{
            'px-4 py-2 rounded-lg font-medium text-white bg-blue-600 hover:bg-blue-700': currentStatus === 'confirmed',
            'px-4 py-2 rounded-lg font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700': currentStatus !== 'confirmed',
          }"
        >
          已确认 ({{ statusCounts.confirmed }})
        </Link>
        <Link
          :href="`/conversations/appointments?status=completed`"
          :class="{
            'px-4 py-2 rounded-lg font-medium text-white bg-green-600 hover:bg-green-700': currentStatus === 'completed',
            'px-4 py-2 rounded-lg font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700': currentStatus !== 'completed',
          }"
        >
          已完成 ({{ statusCounts.completed }})
        </Link>
      </div>

      <!-- Appointments List -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div v-if="appointments.data.length === 0" class="p-6 text-center">
          <p class="text-gray-600 dark:text-gray-400">
            暂无约谈申请
          </p>
        </div>

        <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
          <div
            v-for="appointment in appointments.data"
            :key="appointment.id"
            class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-4">
                  <!-- Status Badge -->
                  <span
                    :class="{
                      'px-3 py-1 rounded-full text-sm font-medium': true,
                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': appointment.status === 'pending',
                      'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': appointment.status === 'confirmed',
                      'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': appointment.status === 'completed',
                      'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': appointment.status === 'cancelled',
                    }"
                  >
                    {{ formatStatus(appointment.status) }}
                  </span>

                  <!-- Appointment Info -->
                  <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                      {{ appointment.student.name }}
                    </h3>
                    <div class="mt-1 flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                      <span>
                        类型: {{ formatType(appointment.appointment_type) }}
                      </span>
                      <span>
                        申请时间: {{ formatDate(appointment.created_at) }}
                      </span>
                    </div>
                    <div v-if="appointment.remarks" class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                      备注: {{ appointment.remarks }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="ml-4 flex items-center gap-2">
                <Link
                  :href="`/conversations/appointments/${appointment.id}`"
                  class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  查看详情
                </Link>

                <button
                  v-if="appointment.status === 'pending'"
                  @click="confirmAppointment(appointment)"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500"
                >
                  确认约谈
                </button>

                <button
                  v-if="appointment.status !== 'completed'"
                  @click="cancelAppointment(appointment)"
                  class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 text-sm font-medium rounded-md text-red-700 dark:text-red-300 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900"
                >
                  取消
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="appointments.links" class="mt-6 flex justify-center gap-2">
        <template v-for="link in appointments.links" :key="link.label">
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
            class="px-4 py-2 rounded border text-sm font-medium bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500 border-gray-300 dark:border-gray-600 cursor-not-allowed"
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

defineProps({
  appointments: Object,
  currentStatus: String,
  statusCounts: Object,
})

const formatStatus = (status) => {
  const statuses = {
    'pending': '待确认',
    'confirmed': '已确认',
    'completed': '已完成',
    'cancelled': '已取消',
  }
  return statuses[status] || status
}

const formatType = (type) => {
  const types = {
    'talk': '谈心',
    'consultation': '咨询',
    'other': '其他',
  }
  return types[type] || type
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('zh-CN')
}

const confirmAppointment = (appointment) => {
  if (confirm(`确认与 ${appointment.student.name} 的约谈？`)) {
    router.patch(`/conversations/appointments/${appointment.id}/confirm`, {}, {
      onSuccess: () => {
        router.visit('/conversations/appointments?status=confirmed')
      },
    })
  }
}

const cancelAppointment = (appointment) => {
  if (confirm(`取消与 ${appointment.student.name} 的约谈？`)) {
    router.delete(`/conversations/appointments/${appointment.id}`, {
      onSuccess: () => {
        router.reload()
      },
    })
  }
}
</script>

