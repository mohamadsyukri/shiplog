<x-layouts.app :title="__('Imports')" :breadcrumbs="[__('Imports') => null]">
    <x-card :title="__('Import history')">
        <x-slot:actions>
            @can('imports.create')
                <x-button icon="bi-upload" :href="route('imports.create')">{{ __('Upload') }}</x-button>
            @endcan
        </x-slot:actions>

        @if ($imports->isEmpty())
            <p class="text-body-secondary mb-0">{{ __('No imports yet.') }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Imported') }}</th>
                            <th>{{ __('File') }}</th>
                            <th>{{ __('Source') }}</th>
                            <th>{{ __('Days') }}</th>
                            <th class="text-end">{{ __('Lanes') }}</th>
                            <th class="text-end">{{ __('Cards created') }}</th>
                            <th class="text-end">{{ __('Cards replaced') }}</th>
                            <th>{{ __('By') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($imports as $import)
                            <tr>
                                <td class="text-nowrap">{{ $import->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-nowrap">{{ $import->file_name }}</td>
                                <td>{{ $import->source->label() }}</td>
                                <td class="text-nowrap">{{ $import->range_from->format('d/m/Y') }} – {{ $import->range_to->format('d/m/Y') }}</td>
                                <td class="text-end">{{ $import->lanes_upserted }}</td>
                                <td class="text-end">{{ $import->entries_created }}</td>
                                <td class="text-end">{{ $import->entries_replaced }}</td>
                                {{-- No user for the shiplog:import command. --}}
                                <td>{{ $import->user?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($imports->hasPages())
                <div class="mt-3">{{ $imports->links('pagination::bootstrap-5') }}</div>
            @endif
        @endif
    </x-card>
</x-layouts.app>
