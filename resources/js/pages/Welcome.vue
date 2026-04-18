<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
  <Head title="浙江财经大学东方学院师德师风系统" />

  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-6 dark:bg-gray-950">
    <main class="w-full max-w-xl rounded-2xl bg-white p-10 shadow-sm dark:bg-gray-900">
      <div class="space-y-3 text-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">浙江财经大学东方学院师德师风系统</h1>
        <p class="text-sm text-gray-600 dark:text-gray-300">统一入口，安全登录，开展师德师风治理与档案管理。</p>
      </div>

      <div class="mt-8 flex justify-center">
        <Link
          v-if="$page.props.auth.user"
          :href="dashboard()"
          class="rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          进入系统
        </Link>
        <Link
          v-else
          :href="login()"
          class="rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          登录
        </Link>
      </div>
    </main>
  </div>
</template>
