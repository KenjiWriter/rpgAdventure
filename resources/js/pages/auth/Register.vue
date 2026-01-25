<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-md bg-slate-900/80 p-8 rounded-xl border border-slate-800">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">Create Account</h2>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-300">Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full bg-slate-800 border-slate-700 rounded text-white" />
                    <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300">Email</label>
                    <input v-model="form.email" type="email" required class="mt-1 block w-full bg-slate-800 border-slate-700 rounded text-white" />
                    <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300">Password</label>
                    <input v-model="form.password" type="password" required class="mt-1 block w-full bg-slate-800 border-slate-700 rounded text-white" />
                    <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300">Confirm Password</label>
                    <input v-model="form.password_confirmation" type="password" required class="mt-1 block w-full bg-slate-800 border-slate-700 rounded text-white" />
                </div>

                <button :disabled="form.processing" class="w-full py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded">
                    Register
                </button>

                <div class="text-center text-sm text-slate-400">
                    Already registered? <Link :href="route('login')" class="text-indigo-400 hover:text-indigo-300">Log in</Link>
                </div>
            </form>
        </div>
    </div>
</template>
