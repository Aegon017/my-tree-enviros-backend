<x-filament::section>
    <div class="space-y-6">
        {{-- Notification Preview Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-2 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
                📱 Notification Preview
            </h3>

            {{-- Mobile Device Mockup --}}
            <div class="mx-auto max-w-sm bg-gray-100 dark:bg-gray-900 rounded-3xl p-4 shadow-2xl">
                {{-- Device Notch --}}
                <div class="bg-gray-900 dark:bg-black h-6 rounded-t-2xl mb-2"></div>

                {{-- Notification Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
                    {{-- Notification Header --}}
                    <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                        <div class="w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ config('app.name') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">now</p>
                        </div>
                    </div>

                    {{-- Notification Content --}}
                    <div class="p-4 space-y-3">
                        @if($notification->title)
                            <h4 class="font-bold text-base text-gray-900 dark:text-gray-100 leading-tight">
                                {{ $notification->title }}
                            </h4>
                        @endif

                        @if($notification->body)
                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                {{ $notification->body }}
                            </p>
                        @endif

                        @if($notification->image)
                            <div class="mt-3 -mx-4 -mb-4">
                                <img 
                                    src="{{ $notification->image }}" 
                                    alt="Notification Image" 
                                    class="w-full h-auto rounded-b-xl object-cover"
                                    style="max-height: 200px;"
                                />
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Device Bottom Bar --}}
                <div class="bg-gray-900 dark:bg-black h-2 rounded-b-2xl mt-2"></div>
            </div>

            {{-- Preview Type Badge --}}
            <div class="mt-4 text-center">
                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full
                    @if($notification->title && $notification->body && $notification->image)
                        bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                    @elseif($notification->title && $notification->image && !$notification->body)
                        bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                    @elseif($notification->image && !$notification->title && !$notification->body)
                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @else
                        bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                    @endif
                ">
                    @if($notification->title && $notification->body && $notification->image)
                        📸 Title + Message + Image
                    @elseif($notification->title && $notification->image && !$notification->body)
                        🖼️ Title + Image Only
                    @elseif($notification->image && !$notification->title && !$notification->body)
                        🎨 Image Only
                    @elseif($notification->title && $notification->body)
                        💬 Title + Message
                    @elseif($notification->title)
                        📝 Title Only
                    @else
                        ⚠️ No Content
                    @endif
                </span>
            </div>
        </div>

        {{-- Notification Details --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Left Column --}}
            <div class="space-y-4">
                <x-filament::section>
                    <x-slot name="heading">
                        📋 Basic Information
                    </x-slot>

                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Title:</dt>
                            <dd class="text-gray-900 dark:text-gray-100 text-right">{{ $notification->title ?: '(None)' }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Message:</dt>
                            <dd class="text-gray-900 dark:text-gray-100 text-right">{{ $notification->body ? Str::limit($notification->body, 30) : '(None)' }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Has Image:</dt>
                            <dd class="text-gray-900 dark:text-gray-100">
                                @if($notification->image)
                                    <span class="inline-flex items-center gap-1 text-success-600 dark:text-success-400">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                        No
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Click Path:</dt>
                            <dd class="text-gray-900 dark:text-gray-100 text-right font-mono text-xs">
                                {{ $notification->path ?: '(None)' }}
                            </dd>
                        </div>
                    </dl>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        👥 Recipients
                    </x-slot>

                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Type:</dt>
                            <dd class="text-gray-900 dark:text-gray-100">
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md
                                    {{ $notification->recipient_type === 'all' ? 'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200' : '' }}
                                    {{ $notification->recipient_type === 'single' ? 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200' : '' }}
                                    {{ $notification->recipient_type === 'multiple' ? 'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200' : '' }}
                                    {{ $notification->recipient_type === 'query' ? 'bg-info-100 text-info-800 dark:bg-info-900 dark:text-info-200' : '' }}
                                ">
                                    {{ \App\Enums\NotificationRecipientTypeEnum::from($notification->recipient_type)->label() }}
                                </span>
                            </dd>
                        </div>
                        @if($notification->recipient_type === 'multiple' && $notification->recipient_ids)
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Selected Users:</dt>
                                <dd class="text-gray-900 dark:text-gray-100">{{ count($notification->recipient_ids) }}</dd>
                            </div>
                        @endif
                        @if($notification->total_recipients > 0)
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Total Recipients:</dt>
                                <dd class="text-gray-900 dark:text-gray-100 font-semibold">{{ $notification->total_recipients }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-filament::section>
            </div>

            {{-- Right Column --}}
            <div class="space-y-4">
                <x-filament::section>
                    <x-slot name="heading">
                        📊 Status & Statistics
                    </x-slot>

                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Status:</dt>
                            <dd class="text-gray-900 dark:text-gray-100">
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md
                                    {{ $notification->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '' }}
                                    {{ $notification->status === 'scheduled' ? 'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200' : '' }}
                                    {{ $notification->status === 'sent' ? 'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200' : '' }}
                                    {{ $notification->status === 'failed' ? 'bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200' : '' }}
                                ">
                                    {{ \App\Enums\NotificationStatusEnum::from($notification->status)->label() }}
                                </span>
                            </dd>
                        </div>
                        @if($notification->status === 'sent')
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Successful:</dt>
                                <dd class="text-success-600 dark:text-success-400 font-semibold">{{ $notification->successful_sends }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Failed:</dt>
                                <dd class="text-danger-600 dark:text-danger-400 font-semibold">{{ $notification->failed_sends }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Success Rate:</dt>
                                <dd class="text-gray-900 dark:text-gray-100 font-semibold">{{ $notification->success_rate }}%</dd>
                            </div>
                        @endif
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <dt class="font-medium text-gray-600 dark:text-gray-400">Created:</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $notification->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                        @if($notification->sent_at)
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Sent At:</dt>
                                <dd class="text-gray-900 dark:text-gray-100">{{ $notification->sent_at->format('M d, Y H:i') }}</dd>
                            </div>
                        @endif
                        @if($notification->scheduled_at)
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <dt class="font-medium text-gray-600 dark:text-gray-400">Scheduled For:</dt>
                                <dd class="text-gray-900 dark:text-gray-100">{{ $notification->scheduled_at->format('M d, Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-filament::section>

                @if($notification->data && count($notification->data) > 0)
                    <x-filament::section>
                        <x-slot name="heading">
                            🔧 Custom Data
                        </x-slot>

                        <dl class="space-y-2 text-sm">
                            @foreach($notification->data as $key => $value)
                                <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                    <dt class="font-medium text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $key }}:</dt>
                                    <dd class="text-gray-900 dark:text-gray-100 text-right font-mono text-xs">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-filament::section>
                @endif
            </div>
        </div>

        {{-- Platform Configurations --}}
        @if($notification->android_config || $notification->ios_config || $notification->web_config)
            <x-filament::section>
                <x-slot name="heading">
                    ⚙️ Platform Configurations
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Android --}}
                    @if($notification->android_config)
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                            <h4 class="font-semibold text-sm text-green-800 dark:text-green-200 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.6 9.48l1.84-3.18c.16-.31.04-.69-.26-.85a.637.637 0 00-.83.22l-1.88 3.24a11.43 11.43 0 00-8.94 0L5.65 5.67a.643.643 0 00-.87-.2c-.28.18-.37.54-.2.87L6.4 9.48A10.81 10.81 0 001 18h22a10.81 10.81 0 00-5.4-8.52zM7 15.25a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zm10 0a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5z"></path>
                                </svg>
                                Android
                            </h4>
                            <dl class="space-y-1 text-xs">
                                @foreach($notification->android_config as $key => $value)
                                    <div class="flex justify-between">
                                        <dt class="text-green-700 dark:text-green-300 font-mono">{{ $key }}:</dt>
                                        <dd class="text-green-900 dark:text-green-100 font-mono">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    {{-- iOS --}}
                    @if($notification->ios_config)
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                            <h4 class="font-semibold text-sm text-blue-800 dark:text-blue-200 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19