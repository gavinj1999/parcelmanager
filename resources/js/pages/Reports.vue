<!-- Add to template -->
<div class="overflow-x-auto mb-8">
    <h2 class="text-lg font-semibold text-gray-100 mb-4">Activity Summary</h2>
    <table class="w-full border bg-gray-900 rounded-lg">
        <thead>
            <tr class="bg-gray-800 text-gray-100">
                <th class="p-3 text-left">Date</th>
                <th class="p-3 text-left">Total Quantity</th>
                <th class="p-3 text-left">Images</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="summary in activitySummary" :key="summary.date" class="border-t hover:bg-gray-800">
                <td class="p-3">
                    <button class="text-blue-400 hover:underline" @click="summary.images.length ? showImage(summary.images[0]) : null" :disabled="!summary.images.length">
                        {{ summary.date }}
                    </button>
                </td>
                <td class="p-3">{{ summary.count }}</td>
                <td class="p-3">
                    {{ summary.images.length ? `${summary.images.length} image(s)` : 'None' }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div v-if="selectedImage" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50" @click="closeModal">
    <div class="relative" @click.stop>
        <img :src="selectedImage" alt="Activity Image" class="max-w-full max-h-[80vh] rounded-lg" />
        <button class="absolute top-2 right-2 bg-gray-800 text-white rounded-full p-2" @click="closeModal">✕</button>
    </div>
</div>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import moment from 'moment';

const props = defineProps({
    activities: Array,
    rounds: Array,
    datePeriods: Array,
});

const selectedImage = ref(null);
const activitySummary = computed(() => {
    const summary = {};
    props.activities.forEach(activity => {
        const date = moment(activity.activity_date).format('YYYY-MM-DD');
        if (!summary[date]) {
            summary[date] = {
                date,
                count: 0,
                images: activity.images?.map(img => `/storage/${img.image_path}`) || [],
            };
        }
        summary[date].count += activity.quantity || 0;
    });
    return Object.values(summary).sort((a, b) => moment(b.date).diff(a.date));
});

const showImage = (url) => {
    selectedImage.value = url;
};
const closeModal = () => {
    selectedImage.value = null;
};
</script>
