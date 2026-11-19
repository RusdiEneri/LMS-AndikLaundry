<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const wibClock = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Jakarta',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
});

const wibTime = ref('--:--:--');

let clockTimer: number | undefined;

function tickWibClock(): void {
    wibTime.value = wibClock.format(new Date());
}

onMounted(() => {
    tickWibClock();
    clockTimer = window.setInterval(tickWibClock, 1000);
});

onBeforeUnmount(() => {
    if (clockTimer !== undefined) {
        window.clearInterval(clockTimer);
    }
});
</script>

<template>
    <svg
        viewBox="0 0 520 520"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
        class="h-auto w-full select-none"
    >
        <defs>
            <clipPath id="wm-drum-clip">
                <circle cx="260" cy="300" r="92" />
            </clipPath>
        </defs>

        <!-- Porthole echo behind the machine -->
        <circle cx="260" cy="260" r="238" class="wm-aura" />
        <circle cx="260" cy="260" r="206" class="wm-ring" stroke-dasharray="5 11" />

        <!-- Ground shadow -->
        <ellipse cx="260" cy="476" rx="164" ry="14" class="wm-shadow" />

        <!-- Feet -->
        <rect x="132" y="456" width="44" height="18" rx="6" class="wm-ink" />
        <rect x="344" y="456" width="44" height="18" rx="6" class="wm-ink" />

        <!-- Cabinet -->
        <rect x="96" y="62" width="328" height="400" rx="30" class="wm-body wm-line-6" />
        <line x1="96" y1="150" x2="424" y2="150" class="wm-line-5" />

        <!-- Control display -->
        <rect x="112" y="84" width="132" height="48" rx="10" class="wm-glass" />
        <text x="178" y="117" text-anchor="middle" class="wm-display">{{ wibTime }}</text>

        <!-- Wordmark on the machine -->
        <text x="262" y="106" class="wm-logo">
            andik<tspan class="wm-logo-dot">.</tspan>
        </text>
        <text x="263" y="121" class="wm-logo-sub">LAUNDRY</text>

        <!-- Program dial -->
        <circle cx="372" cy="108" r="22" class="wm-blue wm-line-4" />
        <line x1="372" y1="108" x2="372" y2="91" class="wm-line-4" stroke-linecap="round" />
        <circle cx="372" cy="108" r="5" class="wm-ink" />

        <!-- Door and drum -->
        <circle cx="260" cy="300" r="126" class="wm-blue wm-line-6" />
        <circle cx="260" cy="300" r="104" class="wm-body wm-line-4-blue" />
        <circle cx="260" cy="300" r="92" class="wm-glass" />

        <g clip-path="url(#wm-drum-clip)">
            <!-- Clothes tumble as the drum turns -->
            <g class="drum-scroll">
                <g transform="translate(260 300)">
                    <path
                        d="M-46 22 L-18 -16 L-6 -8 L0 -28 L26 -28 L32 -8 L44 -16 L72 22 L44 46 L32 32 L32 74 L-6 74 L-6 32 L-18 46 Z"
                        class="wm-teal wm-line-3"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M-62 40 C -40 18, -10 22, -2 44 C 8 70, -22 88, -46 74 C -70 60, -78 48, -62 40 Z"
                        class="wm-yellow wm-line-3"
                    />
                    <path
                        d="M10 -52 C 44 -68, 70 -40, 58 -8 C 48 18, 16 24, 4 4 C -6 -14, -4 -40, 10 -52 Z"
                        class="wm-blue-fabric wm-line-3"
                    />
                    <path
                        d="M-30 -50 C -18 -66, 6 -62, 10 -44 C 14 -26, -2 -14, -18 -20 C -34 -26, -40 -40, -30 -50 Z"
                        class="wm-suds wm-line-3"
                    />
                </g>
            </g>

            <!-- Water pools at the bottom, then sloshes with the drum -->
            <g class="drum-water">
                <path
                    d="M168 352 Q 214 338 260 352 T 352 352 L 352 392 L 168 392 Z"
                    class="wm-water"
                    opacity="0.55"
                />
                <path d="M168 352 Q 214 338 260 352 T 352 352" class="wm-water-line" />
            </g>

            <!-- Suds drift on the moving surface -->
            <g class="drum-suds">
                <circle cx="196" cy="368" r="9" class="wm-suds" />
                <circle cx="218" cy="356" r="12" class="wm-suds" />
                <circle cx="244" cy="372" r="8" class="wm-suds" />
                <circle cx="306" cy="360" r="11" class="wm-suds" />
                <circle cx="330" cy="374" r="7" class="wm-suds" />
            </g>

            <!-- Bubbles surface and fade -->
            <g class="drum-bubbles">
                <circle cx="228" cy="368" r="2.5" class="wm-bubble" />
                <circle cx="282" cy="374" r="2" class="wm-bubble" />
                <circle cx="316" cy="366" r="2.8" class="wm-bubble" />
            </g>

            <!-- Glass reflection -->
            <path d="M190 250 A 76 76 0 0 1 316 236 A 70 70 0 0 0 200 266 Z" class="wm-glass-shine" />
        </g>

        <circle cx="260" cy="300" r="92" class="wm-line-2-blue" />

        <!-- Door handle -->
        <rect x="392" y="270" width="14" height="60" rx="7" class="wm-ink" />
    </svg>
</template>

<style scoped>
.wm-aura {
    fill: var(--color-primary-100);
    opacity: 0.55;
}

.wm-ring {
    stroke: var(--color-accent-100);
    stroke-width: 2;
}

.wm-shadow {
    fill: var(--color-navy-950);
    opacity: 0.12;
}

.wm-body {
    fill: var(--color-surface);
}

.wm-blue {
    fill: var(--color-primary-100);
}

.wm-ink {
    fill: var(--color-navy-900);
}

.wm-glass {
    fill: var(--color-navy-950);
}

.wm-teal {
    fill: var(--color-accent-500);
}

.wm-yellow {
    fill: var(--color-warning);
}

.wm-blue-fabric {
    fill: var(--color-primary-500);
}

.wm-suds {
    fill: var(--color-surface);
    opacity: 0.88;
}

.wm-water {
    fill: var(--color-primary-600);
}

.wm-water-line {
    stroke: var(--color-primary-100);
    stroke-width: 3;
    opacity: 0.8;
}

.drum-water {
    animation: water-slosh 4.6s ease-in-out infinite;
    transform-box: view-box;
    transform-origin: 260px 352px;
    will-change: transform;
}

.drum-suds .wm-suds {
    animation: suds-drift 3.8s ease-in-out infinite;
}

.drum-suds .wm-suds:nth-child(2) {
    animation-delay: -0.9s;
    animation-duration: 4.6s;
}

.drum-suds .wm-suds:nth-child(3) {
    animation-delay: -1.8s;
    animation-duration: 3.2s;
}

.drum-suds .wm-suds:nth-child(4) {
    animation-delay: -2.4s;
    animation-duration: 4.1s;
}

.drum-suds .wm-suds:nth-child(5) {
    animation-delay: -0.4s;
    animation-duration: 3.5s;
}

.drum-bubbles .wm-bubble {
    fill: var(--color-surface);
    opacity: 0;
    transform-box: fill-box;
    transform-origin: center;
    animation: bubble-rise 4.8s ease-in infinite;
}

.drum-bubbles .wm-bubble:nth-child(2) {
    animation-delay: -1.6s;
    animation-duration: 5.6s;
}

.drum-bubbles .wm-bubble:nth-child(3) {
    animation-delay: -3.1s;
    animation-duration: 4.2s;
}

@keyframes water-slosh {
    0%,
    100% {
        transform: translateY(0) rotate(0deg);
    }
    25% {
        transform: translateY(-1.5px) rotate(-2deg);
    }
    50% {
        transform: translateY(1.5px) rotate(0deg);
    }
    75% {
        transform: translateY(-1.5px) rotate(2deg);
    }
}

@keyframes suds-drift {
    0%,
    100% {
        transform: translate(0, 0);
    }
    50% {
        transform: translate(3px, -3px);
    }
}

@keyframes bubble-rise {
    0% {
        transform: translateY(4px) scale(0.7);
        opacity: 0;
    }
    15% {
        opacity: 0.8;
    }
    85% {
        opacity: 0.6;
    }
    100% {
        transform: translateY(-16px) scale(1.1);
        opacity: 0;
    }
}

.wm-glass-shine {
    fill: var(--color-surface);
    opacity: 0.16;
}

.wm-line-2-blue {
    stroke: var(--color-primary-600);
    stroke-width: 2;
    opacity: 0.6;
}

.wm-line-3 {
    stroke: var(--color-navy-950);
    stroke-width: 3;
}

.wm-line-4 {
    stroke: var(--color-navy-900);
    stroke-width: 4;
}

.wm-line-4-blue {
    stroke: var(--color-primary-600);
    stroke-width: 4;
}

.wm-line-5 {
    stroke: var(--color-navy-900);
    stroke-width: 5;
}

.wm-line-6 {
    stroke: var(--color-navy-900);
    stroke-width: 6;
}

.wm-display {
    fill: var(--color-accent-500);
    font-family: var(--font-mono);
    font-size: 22px;
    font-weight: 700;
}

.wm-logo {
    fill: var(--color-navy-900);
    font-family: var(--font-heading);
    font-size: 18px;
    font-weight: 800;
}

.wm-logo-dot {
    fill: var(--color-accent-600);
}

.wm-logo-sub {
    fill: var(--color-primary-700);
    font-family: var(--font-body);
    font-size: 6.5px;
    font-weight: 700;
    letter-spacing: 0.22em;
}
</style>
