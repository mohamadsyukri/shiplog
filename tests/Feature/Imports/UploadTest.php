<?php

use App\Enums\ImportSource;
use App\Models\Entry;
use App\Models\Import;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    seedAccess();
    $this->uploader = userWithPermissions('imports.index', 'imports.create');
});

function shiplogUpload(string $json, string $name = 'shiplog-2026-10-20_to_2026-10-21.json'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $json);
}

// 1. Access gate
it('sends guests to the sign-in page', function (): void {
    $this->get(route('imports.index'))->assertRedirect(route('login'));
});

it('refuses the history and the upload to a user without permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('imports.index'))->assertForbidden();
    $this->actingAs($user)->get(route('imports.create'))->assertForbidden();
    $this->actingAs($user)->post(route('imports.store'), ['file' => shiplogUpload(shiplogFile())])->assertForbidden();
    nothingWritten();
});

it('shows the history to a reader without the Upload button, and refuses them the upload', function (): void {
    $reader = userWithPermissions('imports.index');

    $this->actingAs($reader)->get(route('imports.index'))->assertOk()->assertDontSee(route('imports.create'));
    $this->actingAs($reader)->get(route('imports.create'))->assertForbidden();
    $this->actingAs($reader)->post(route('imports.store'), ['file' => shiplogUpload(shiplogFile())])->assertForbidden();
    nothingWritten();
});

// 3. Happy upload
it('imports an uploaded file, says what it added and lists it in the history', function (): void {
    $this->actingAs($this->uploader)
        ->post(route('imports.store'), ['file' => shiplogUpload(shiplogFile())])
        ->assertRedirect(route('imports.index'))
        ->assertSessionHas('success', 'Imported shiplog-2026-10-20_to_2026-10-21.json: 2 cards created, 0 replaced, 3 lanes.');

    $import = Import::query()->sole();
    expect($import->source)->toBe(ImportSource::Upload)
        ->and($import->user_id)->toBe($this->uploader->id)
        ->and($import->file_name)->toBe('shiplog-2026-10-20_to_2026-10-21.json')
        ->and(Entry::query()->count())->toBe(2);

    $this->actingAs($this->uploader)->get(route('imports.index'))
        ->assertSee(route('imports.create'))
        ->assertSeeInOrder(['shiplog-2026-10-20_to_2026-10-21.json', 'Upload', '20/10/2026 – 21/10/2026', $this->uploader->name]);
});

it('lists a command import with no uploader, newest first', function (): void {
    Import::factory()->create(['file_name' => 'older.json']);
    Import::factory()->for($this->uploader)->create(['file_name' => 'newer.json', 'source' => ImportSource::Upload]);

    $this->actingAs($this->uploader)->get(route('imports.index'))
        ->assertSeeInOrder(['newer.json', $this->uploader->name, 'older.json', 'Command', '—']);
});

it('shows 20 imports per page with links to the rest', function (): void {
    Import::factory()->count(21)->sequence(fn ($sequence): array => ['file_name' => sprintf('file-%02d.json', $sequence->index + 1)])->create();

    $this->actingAs($this->uploader)->get(route('imports.index'))
        ->assertSee('file-21.json')
        ->assertSee('file-02.json')
        ->assertDontSee('file-01.json')
        ->assertSee(route('imports.index', ['page' => 2]), false);

    $this->actingAs($this->uploader)->get(route('imports.index', ['page' => 2]))->assertSee('file-01.json');
});

// 4. Validation rejections
it('refuses a file the upload rules do not allow and writes nothing', function (?UploadedFile $file, string $message): void {
    $this->actingAs($this->uploader)
        ->from(route('imports.create'))
        ->post(route('imports.store'), ['file' => $file])
        ->assertRedirect(route('imports.create'))
        ->assertSessionHasErrors(['file' => $message]);

    nothingWritten();
})->with([
    'no file' => [fn () => null, 'The file field is required.'],
    'over 2 MB' => [fn () => shiplogUpload(str_repeat(' ', 2049 * 1024).shiplogFile()), 'The file field must not be greater than 2048 kilobytes.'],
    'not a JSON or text file' => [fn () => UploadedFile::fake()->image('board.png'), 'The file field must have one of the following extensions: json, txt.'],
]);

it('refuses a file with wrong cards, lists every problem on the form and writes nothing', function (): void {
    $broken = shiplogUpload(shiplogFile(['lanes.0.color' => 'blue', 'entries.1.summary' => '']));

    $this->actingAs($this->uploader)
        ->from(route('imports.create'))
        ->followingRedirects()
        ->post(route('imports.store'), ['file' => $broken])
        ->assertOk()
        ->assertSee('The file was refused: 2 problems.')
        ->assertSeeInOrder([
            'lanes[0] color "blue" is not written #rrggbb.',
            'entries[1] 2026-10-21 lane "side-project" summary is empty.',
        ]);

    nothingWritten();
});

// 8. Links
it('shows Imports in the sidebar only to users who may open it', function (): void {
    $this->actingAs($this->uploader)->get(route('board.index'))->assertSee(route('imports.index'));
    $this->actingAs(User::factory()->create())->get(route('board.index'))->assertDontSee(route('imports.index'));
});

it('points an empty board to the upload screen for users who may upload', function (): void {
    $this->actingAs($this->uploader)->get(route('board.index'))->assertSee(route('imports.create'));
    $this->actingAs(userWithPermissions('imports.index'))->get(route('board.index'))->assertDontSee(route('imports.create'));
});
