<template>
    <div class="h-full w-full flex items-center justify-center mt-5">
        <div class="flex flex-col h-96 w-1/3 bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-auto p-3 space-y-3 flex flex-col prose" style="flex: 1 1 auto;">
                <div
                    v-for="(message, index) in chatMessages"
                    :key="index"
                    :class="{
    'self-end bg-blue-500 text-white': message.role === 'user',
    'self-start bg-gray-200': message.role === 'assistant',
    'self-start text-orange-500': message.role === 'FunctionCall',
  }"
                    class="px-3 py-2 rounded-xl max-w-sm"
                >                        <p class="text-sm">{{ message.content }}</p>
                </div>
                <div v-if="isWaiting" class="p-3 flex justify-center">
                    <div class="inline-flex rounded-full h-6 w-6 bg-blue-500">

                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="border-t p-3" style="flex: 0 0 auto;">
                <div class="relative">
                    <input
                        v-model="newMessage"
                        type="text"
                        placeholder="Type a message"
                        @keydown.enter.prevent="sendMessage"
                        class="pl-10 pr-3 py-2 w-full rounded-full text-sm border border-gray-300 placeholder-gray-500"
                    />
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7-6v6l4-3-4-3z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
import {onMounted, ref} from 'vue';


// Initialize newMessage and chatMessages
let newMessage = ref("");
let chatMessages = ref([]);
let isWaiting = ref(false);

const url = "/chat-api";

function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

const uuid = ref(generateUUID());

onMounted(() => {
    // Initialize a variable to keep track of the last assistant message
    let lastAssistantMessage

    window.Echo.channel(`upload-channel.${uuid.value}`).listen("ChatEvent", (event) => {
        const deltaMessage = event.message.delta;
        const finishReason = event.message.finish_reason;
        if (finishReason !== "stop") {
            if (!lastAssistantMessage) {
                // Create a new assistant message using ref to make content reactive
                lastAssistantMessage = {
                    role: 'assistant',
                    content: ref(""), // Using ref
                };
                chatMessages.value.push(lastAssistantMessage);
            }
            // Append the delta content directly to the chatMessages content
            lastAssistantMessage.content.value += deltaMessage.content; // Accessing the value of the ref
        } else {
            // Reset the lastAssistantMessage reference when the finish_reason is 'stop'
            lastAssistantMessage = null;
        }
    })
});



// Add method for sending a message
async function sendMessage() {
    if (newMessage.value.trim() !== "") {
        // Push user message
        chatMessages.value.push({
            role: "user",
            content: newMessage.value,
        });

        isWaiting.value = true;

        const interimMessage = {
            role: "assistant",
            content: "Processing your request...",
        };
        chatMessages.value.push(interimMessage);

        const interimMessageIndex = chatMessages.value.length - 1;

        // Make the post to the chat-api
        const { data } = await axios.post(url, { userMessage: newMessage.value, uuid: uuid.value });

        // Clear input field
        newMessage.value = "";

        // Push assistant message
        //chatMessages.value.push(data);
        isWaiting.value = false;

    }
}
</script>
