<template>
  <div class="flex flex-col items-center justify-center gap-0.5 h-full py-1">
    <span class="font-bold text-[13px] leading-tight" :style="{ color: resolvedColor }">
      {{ displayValue }}
    </span>
    <span v-if="originalScore != null" class="text-[10px] leading-tight opacity-70" :style="{ color: originalColor }">
      asli: {{ originalScore }}
    </span>
    <span class="text-[9px] leading-tight text-gray-400">
      {{ modeLabel }}
    </span>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  params: { type: Object, required: true },
});

const resolveRemedialScore = (examScore, remedialScore, mode) => {
  switch (mode) {
    case "replace": return Math.max(examScore, remedialScore);
    case "average": return Math.round(((examScore + remedialScore) / 2) * 100) / 100;
    case "custom": return remedialScore;
    default: return Math.max(examScore, remedialScore);
  }
};

const originalScore = computed(() => {
  const val = props.params.value;
  return val != null ? Number(val) : null;
});

const remedialData = computed(() => {
  const data = props.params.data;
  if (!data) return null;

  // cellRendererParams di-merge langsung ke params
  const assignment = props.params.assignment;
  const remedialByParent = props.params.remedialByParent;
  if (!assignment || !remedialByParent) return null;

  const remList = remedialByParent[assignment.id] || [];
  const rem = remList[0];
  if (!rem) return null;

  const remScore = data.assignments?.[rem.id];
  if (remScore == null) return null;

  const mode = data.remedial_modes?.[assignment.id] || "replace";
  return { remedialScore: Number(remScore), mode };
});

const resolvedScore = computed(() => {
  if (originalScore.value == null || !remedialData.value) return originalScore.value;
  return resolveRemedialScore(originalScore.value, remedialData.value.remedialScore, remedialData.value.mode);
});

const displayValue = computed(() => {
  return resolvedScore.value != null ? resolvedScore.value : "—";
});

const modeLabel = computed(() => {
  if (!remedialData.value) return "";
  switch (remedialData.value.mode) {
    case "replace": return "Ganti";
    case "average": return "Rata-rata";
    case "custom": return "Manual";
    default: return "Ganti";
  }
});

const resolvedColor = computed(() => {
  const val = resolvedScore.value;
  if (val == null) return "#d1d5db";
  return val >= 75 ? "#16a34a" : "#E02E2B";
});

const originalColor = computed(() => {
  const val = originalScore.value;
  if (val == null) return "#9ca3af";
  return val >= 75 ? "#16a34a" : "#E02E2B";
});
</script>
