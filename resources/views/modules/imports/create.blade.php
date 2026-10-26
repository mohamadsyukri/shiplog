<x-layouts.app :title="__('Upload a Shiplog file')" :breadcrumbs="[__('Imports') => route('imports.index'), __('Upload') => null]">
    <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data">
        @csrf
        <x-card :title="__('Shiplog file')">
            <x-form.input type="file" name="file" :label="__('File')" accept=".json,.txt" required
                :help="__('A Shiplog file (.json), at most :size MB. Each card replaces the one already on the board for that day and lane; nothing is deleted.', ['size' => \App\Http\Requests\Imports\StoreImportRequest::MAX_KILOBYTES / 1024])" />

            {{-- ImportService lists every problem in the file, so they show together, not one at a time. --}}
            @if ($errors->has('problems'))
                <div class="alert alert-danger mb-0" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->get('problems') as $problem)
                            <li>{{ $problem }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-slot:footer>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>{{ __('Upload') }}</button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.app>
