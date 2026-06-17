<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BarChart3,
    ClipboardCheck,
    Database,
    FileDown,
    ShieldCheck,
} from 'lucide-vue-next';

const capabilityCards = [
    {
        title: '一人一档',
        description: '统一沉淀教师基础信息、年度扣分、预警记录和处置台账。',
        icon: Database,
    },
    {
        title: '五维评分',
        description:
            '政治素养、教育教学、学术诚信、为人师表、工作纪律各 20 分。',
        icon: BarChart3,
    },
    {
        title: '三级预警',
        description: '按总分和单维扣分自动识别蓝色、黄色、红色风险。',
        icon: AlertTriangle,
    },
    {
        title: '闭环处置',
        description: '支撑核定、整改、回执、归档等师德治理工作流。',
        icon: ClipboardCheck,
    },
];
</script>

<template>
    <Head title="师德师风监督预警平台" />

    <div
        class="min-h-screen bg-slate-100 px-4 py-6 text-slate-950 sm:px-6 lg:px-8"
    >
        <main class="mx-auto flex w-full max-w-6xl flex-col gap-6">
            <section
                class="rounded-2xl border border-slate-200 bg-white px-6 py-6 shadow-sm sm:px-8"
            >
                <div
                    class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1
                            class="text-3xl font-bold tracking-normal text-slate-950"
                        >
                            师德师风监督预警平台
                        </h1>
                        <p class="mt-2 text-sm text-slate-600">
                            浙江财经大学东方学院 · 师德治理数字化工作台
                        </p>
                    </div>

                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        进入工作台
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        登录系统
                    </Link>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <div class="grid gap-4 md:grid-cols-3">
                    <div
                        class="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-slate-600">年度总分模型</p>
                            <ShieldCheck class="size-5 text-blue-600" />
                        </div>
                        <p class="mt-2 text-2xl font-bold text-slate-950">
                            100 分
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            五个维度各 20 分，按核定等级自动扣分
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-rose-200 bg-rose-50 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-rose-700">风险预警</p>
                            <AlertTriangle class="size-5 text-rose-600" />
                        </div>
                        <p class="mt-2 text-2xl font-bold text-rose-700">
                            蓝 / 黄 / 红
                        </p>
                        <p class="mt-1 text-xs text-rose-600">
                            按总分区间和单维扣分自动升级
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-amber-200 bg-amber-50 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-amber-700">报表支撑</p>
                            <FileDown class="size-5 text-amber-600" />
                        </div>
                        <p class="mt-2 text-2xl font-bold text-amber-700">
                            Excel 导出
                        </p>
                        <p class="mt-1 text-xs text-amber-600">
                            档案明细、部门汇总、维度统计、预警清单
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-4">
                    <article
                        v-for="card in capabilityCards"
                        :key="card.title"
                        class="rounded-lg border border-slate-200 bg-white p-4"
                    >
                        <component
                            :is="card.icon"
                            class="size-5 text-slate-700"
                        />
                        <h2 class="mt-3 text-base font-semibold text-slate-950">
                            {{ card.title }}
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ card.description }}
                        </p>
                    </article>
                </div>

                <div
                    class="mt-5 overflow-hidden rounded-lg border border-slate-200"
                >
                    <div
                        class="grid grid-cols-12 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700"
                    >
                        <span class="col-span-3">业务模块</span>
                        <span class="col-span-5">主要内容</span>
                        <span class="col-span-2">风险颜色</span>
                        <span class="col-span-2 text-right">状态</span>
                    </div>
                    <div class="divide-y divide-slate-100 text-sm">
                        <div class="grid grid-cols-12 px-4 py-3">
                            <span class="col-span-3 font-medium text-blue-700"
                                >师德档案</span
                            >
                            <span class="col-span-5 text-slate-600"
                                >人员档案、年度评分、历年扣分</span
                            >
                            <span class="col-span-2 text-slate-600">蓝色</span>
                            <span class="col-span-2 text-right text-slate-950"
                                >已接入</span
                            >
                        </div>
                        <div class="grid grid-cols-12 px-4 py-3">
                            <span class="col-span-3 font-medium text-amber-700"
                                >违规核定</span
                            >
                            <span class="col-span-5 text-slate-600"
                                >A级、B级、C级核定扣分</span
                            >
                            <span class="col-span-2 text-slate-600">黄色</span>
                            <span class="col-span-2 text-right text-slate-950"
                                >运行中</span
                            >
                        </div>
                        <div class="grid grid-cols-12 px-4 py-3">
                            <span class="col-span-3 font-medium text-rose-700"
                                >整改闭环</span
                            >
                            <span class="col-span-5 text-slate-600"
                                >预警、处置、回执、归档</span
                            >
                            <span class="col-span-2 text-slate-600">红色</span>
                            <span class="col-span-2 text-right text-slate-950"
                                >持续完善</span
                            >
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
