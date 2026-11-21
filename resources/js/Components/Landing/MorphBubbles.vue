<script setup lang="ts">
withDefaults(
    defineProps<{
        count?: number;
        variant?: 'light' | 'dark';
    }>(),
    {
        count: 5,
        variant: 'light',
    },
);
</script>

<template>
    <div
        class="pointer-events-none absolute inset-0 overflow-hidden select-none"
        aria-hidden="true"
    >
        <!-- Morphing Ambient Water/Soap Blobs (Depth of Field) -->
        <div
            v-if="variant === 'light'"
            class="morph-ambient-1 absolute -left-20 top-1/4 h-72 w-72 rounded-full bg-gradient-to-tr from-accent-200/40 via-primary-100/50 to-transparent blur-3xl"
        ></div>
        <div
            v-if="variant === 'light'"
            class="morph-ambient-2 absolute -right-24 top-2/3 h-80 w-80 rounded-full bg-gradient-to-bl from-primary-200/40 via-accent-100/45 to-transparent blur-3xl"
        ></div>

        <div
            v-if="variant === 'dark'"
            class="morph-ambient-1 absolute -left-20 top-1/3 h-72 w-72 rounded-full bg-gradient-to-tr from-accent-500/15 via-primary-600/15 to-transparent blur-3xl"
        ></div>
        <div
            v-if="variant === 'dark'"
            class="morph-ambient-2 absolute -right-24 top-1/2 h-80 w-80 rounded-full bg-gradient-to-bl from-primary-500/15 via-accent-400/15 to-transparent blur-3xl"
        ></div>

        <!-- Floating Glassy Soap/Water Bubbles ("Muncul Timbul") -->
        <div
            class="bubble bubble-1 absolute left-[8%] top-[15%] h-16 w-16"
            :class="variant === 'light' ? 'bubble-light' : 'bubble-dark'"
        ></div>
        <div
            class="bubble bubble-2 absolute right-[10%] top-[25%] h-24 w-24"
            :class="variant === 'light' ? 'bubble-light' : 'bubble-dark'"
        ></div>
        <div
            class="bubble bubble-3 absolute left-[15%] top-[65%] h-20 w-20"
            :class="variant === 'light' ? 'bubble-light' : 'bubble-dark'"
        ></div>
        <div
            class="bubble bubble-4 absolute right-[18%] top-[75%] h-14 w-14"
            :class="variant === 'light' ? 'bubble-light' : 'bubble-dark'"
        ></div>
        <div
            v-if="count > 4"
            class="bubble bubble-5 absolute left-[48%] top-[45%] h-12 w-12"
            :class="variant === 'light' ? 'bubble-light' : 'bubble-dark'"
        ></div>
    </div>
</template>

<style scoped>
/* Organic Morphing Shape */
@keyframes morph-bubble {
    0%, 100% {
        border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
        transform: translate3d(0, 0, 0) rotate(0deg);
    }
    33% {
        border-radius: 53% 47% 65% 35% / 42% 58% 42% 58%;
        transform: translate3d(10px, -14px, 0) rotate(120deg);
    }
    66% {
        border-radius: 40% 60% 70% 30% / 50% 40% 60% 50%;
        transform: translate3d(-10px, 12px, 0) rotate(240deg);
    }
}

/* Ambient Slow Pulse */
@keyframes ambient-morph {
    0%, 100% {
        border-radius: 65% 35% 55% 45% / 45% 55% 45% 55%;
        transform: scale(1) translate3d(0, 0, 0);
    }
    50% {
        border-radius: 40% 60% 35% 65% / 60% 40% 60% 40%;
        transform: scale(1.1) translate3d(-15px, 15px, 0);
    }
}

.morph-ambient-1 {
    animation: ambient-morph 16s ease-in-out infinite;
    will-change: border-radius, transform;
}

.morph-ambient-2 {
    animation: ambient-morph 20s ease-in-out infinite;
    animation-delay: -7s;
    will-change: border-radius, transform;
}

/* Glassy Bubble Styling */
.bubble {
    will-change: border-radius, transform;
    animation: morph-bubble 9s ease-in-out infinite;
}

.bubble-light {
    background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.85) 0%, rgba(201, 240, 230, 0.35) 45%, rgba(31, 127, 196, 0.15) 85%);
    border: 1px solid rgba(255, 255, 255, 0.7);
    box-shadow: inset -2px -2px 6px rgba(31, 127, 196, 0.15),
                inset 2px 2px 6px rgba(255, 255, 255, 0.9),
                0 8px 20px -4px rgba(57, 197, 168, 0.25);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
}

.bubble-dark {
    background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.3) 0%, rgba(57, 197, 168, 0.2) 50%, rgba(31, 127, 196, 0.1) 85%);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: inset -2px -2px 6px rgba(0, 0, 0, 0.2),
                inset 2px 2px 6px rgba(255, 255, 255, 0.4),
                0 8px 25px -4px rgba(57, 197, 168, 0.35);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
}

.bubble-1 {
    animation-duration: 11s;
}

.bubble-2 {
    animation-duration: 14s;
    animation-delay: -3s;
}

.bubble-3 {
    animation-duration: 10s;
    animation-delay: -5s;
}

.bubble-4 {
    animation-duration: 13s;
    animation-delay: -8s;
}

.bubble-5 {
    animation-duration: 12s;
    animation-delay: -2s;
}

@media (prefers-reduced-motion: reduce) {
    .bubble,
    .morph-ambient-1,
    .morph-ambient-2 {
        animation: none !important;
    }
}
</style>
