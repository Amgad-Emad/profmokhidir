@use('App\Constants\Locales')
<div>
    <x-admin.page-header :eyebrow="__('Media')" :title="$isNew ? __('New video') : __('Edit video')" :intro="__('A lecture, interview or ceremony. Link to YouTube or Vimeo, or upload the file.')" />
    <x-admin.flash />

    <x-admin.edit-layout :editor="$editor" :sections="['video' => __('The recording'), 'source' => __('Where it plays'), 'filing' => __('Filing'), 'poster' => __('Poster image')]" class="mt-10">
        <x-admin.form-section id="video" :title="__('The recording')">
            <x-admin.locale-tabs id="video-text" :label="__('The recording')" :fields="['title', 'event', 'location', 'description']">
                @foreach (Locales::all() as $locale)
                    @php $field = $locale === Locales::DEFAULT ? 'form.' : 'form.translations.'.$locale.'.'; @endphp
                    <x-admin.locale-panel tabs="video-text" :locale="$locale">
                        <x-form.input :model="$field.'title'" :label="__('Title')" :required="$locale === Locales::DEFAULT" :hint="__('For example Inaugural lecture, University of Khartoum.')" />
                        <div class="form-row">
                            <x-form.input class="form-col--half" :model="$field.'event'" :label="__('Event')" :hint="__('The occasion.')" />
                            <x-form.input class="form-col--half" :model="$field.'location'" :label="__('Location')" :hint="__('City or hall.')" />
                        </div>
                        <x-form.textarea :model="$field.'description'" :label="__('Description')" rows="4" :hint="__('What the recording is about, in two or three sentences.')" />
                    </x-admin.locale-panel>
                @endforeach
            </x-admin.locale-tabs>
            <div class="form-row">
                <x-form.date class="form-col--third" model="form.date" :label="__('Date')" :hint="__('When it was recorded.')" />
            </div>
        </x-admin.form-section>
        <x-admin.form-section id="source" :title="__('Where it plays')">
            <x-form.select model="form.provider" :label="__('Source')" :options="$providers" required live :hint="__('Where the video is kept.')" />
            <x-form.input model="form.media_url" :label="__('Video link')" type="url" placeholder="https://" :hint="__('The YouTube, Vimeo or other page address. Not needed for an uploaded file.')" />
            <x-form.upload model="videoFile" :label="__('Uploaded video file')" :media="$videoFile" :href="$videoFileUrl" :remove="$videoFile ? 'removeVideoFile' : null" :accept="$videoAccept" :hint="$videoHint" />
            <x-form.input model="form.captions_url" :label="__('Captions link')" type="url" placeholder="https://" :hint="__('A WebVTT captions file, so people who cannot hear can follow.')" />
        </x-admin.form-section>
        <x-admin.form-section id="filing" :title="__('Filing')">
            <div class="form-row">
                <x-form.select class="form-col--half" model="form.decade" :label="__('Decade')" :options="$decades" :placeholder="__('From the date')" :hint="__('Where the recording is filed when videos are grouped by decade. Leave empty to use its date.')" />
                <x-form.toggle model="form.is_featured" :label="__('Featured recording')" :hint="__('Opens the Videos page. Keep it to one or two.')" class="form-col--half" />
            </div>
            @unless ($isNew)
                <x-form.input model="form.slug" :label="__('Web address')" :hint="__('The last part of the page\'s address. Leave it as it is unless the link must change.')" :mono="true" dir="ltr" />
            @endunless
        </x-admin.form-section>

        <x-admin.form-section id="poster" :title="__('Poster image')" :form="false">
            <x-admin.image-grid :label="__('Poster image')" :media="$media" :missing="$missing" :images="$images" :details-required="$detailsRequired" :accept="$imageAccept" :hint="$imageHint" :confirming-id="$confirmingImageId" :can-upload="! $isNew" />
            @if ($isNew)
                <p class="text-sm font-normal text-ink-muted">{{ __('The poster can be added once the video has a title and has been saved once.') }}</p>
            @endif
        </x-admin.form-section>
    </x-admin.edit-layout>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
