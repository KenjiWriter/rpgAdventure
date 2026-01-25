<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-md bg-slate-900/80 p-8 rounded-xl border border-slate-800">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">Welcome Back</h2>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-300">Email</label>
                    <input v-model="form.email" type="email" required class="mt-1 block w-full bg-slate-800 border-slate-700 rounded text-white" />
                    <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300">Password</label>
                    <input v-model="form.password" type="password" required class="mt-1 block w-full bg-slate-800 border-slate-700 rounded text-white" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input v-model="form.remember" type="checkbox" class="bg-slate-800 border-slate-700 rounded text-indigo-500" />
                        <span class="ml-2 text-sm text-slate-400">Remember me</span>
                    </label>
                    <Link :href="route('password.request')" class="text-sm text-indigo-400 hover:text-indigo-300">Forgot password?</Link>
                </div>

                <button :disabled="form.processing" class="w-full py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded">
                    Log in
                </button>

                <div class="text-center text-sm text-slate-400">
                    Don't have an account? <Link :href="route('register')" class="text-indigo-400 hover:text-indigo-300">Register</Link>
                </div>
            </form>
        </div>
    </div>
</template>
