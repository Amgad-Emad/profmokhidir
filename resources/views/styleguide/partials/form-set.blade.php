{{-- The full set of form controls, in plain-form mode. `$suffix` keeps ids unique per specimen. --}}
@use('App\Constants\FormControls')
<div class="space-y-10" data-form-set>
    <x-form.error-summary :intro="FormControls::ERROR_SUMMARY_INTRO" :messages="['name-'.$suffix => 'Enter your name.', 'year-'.$suffix => FormControls::yearMessage()]" />

    <div class="grid gap-8 sm:grid-cols-2">
        <x-form.input :name="'name-'.$suffix" label="Name" required autocomplete="off" hint="As it should appear on the tribute." />
        <x-form.input :name="'email-'.$suffix" label="Email" type="email" autocomplete="off" hint="Never shown on the site." />
    </div>
    <x-form.textarea :name="'message-'.$suffix" label="Message" required :maxlength="600" rows="3" value="He taught us that knowledge is the light we pass on." hint="Grows as you write." />
    <div class="grid gap-8 sm:grid-cols-2">
        <x-form.select :name="'relationship-'.$suffix" label="Relationship" :placeholder="FormControls::SELECT_PLACEHOLDER" required :options="['student' => 'Former student', 'colleague' => 'Colleague', 'family' => 'Family']" />
        <x-form.combobox :name="'album-'.$suffix" label="Album" placeholder="Type to find an album" hint="For long lists." :options="['khartoum' => 'Khartoum, 1975–1992', 'field' => 'Field seasons', 'graduations' => 'Graduations', 'conferences' => 'Conferences abroad', 'family' => 'Family archive', 'letters' => 'Letters and notebooks']" />
    </div>
    <div class="grid gap-8 sm:grid-cols-2">
        <x-form.date :name="'date-'.$suffix" label="Date" value="1984-03-14" />
        <x-form.year :name="'year-'.$suffix" label="Year" hint="Four digits." />
    </div>
    <x-form.tags :name="'topics-'.$suffix" label="Topic tags" :value="['Agronomy', 'Soil science']" :suggestions="['Rural development', 'Irrigation', 'Teaching']" />
    <div class="grid gap-8 sm:grid-cols-2">
        <x-form.password :name="'password-'.$suffix" label="New password" autocomplete="new-password" :strength="true" :required="false" />
        <x-form.otp :name="'code-'.$suffix" label="Authentication code" :autofocus="false" :required="false" />
    </div>
    <x-form.radio-group :name="'status-'.$suffix" label="Status" variant="segmented" value="draft" :optional="false" :options="['draft' => 'Draft', 'published' => 'Published']" :icons="['draft' => 'clock', 'published' => 'check']" />
    <x-form.radio-group :name="'type-'.$suffix" label="Type" value="book" :options="['book' => 'Book', 'article' => ['Article', 'In a journal or proceedings'], 'chapter' => 'Chapter']" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.checkbox :name="'agree-'.$suffix" required>I have read the community guidelines.</x-form.checkbox>
        <x-form.toggle :name="'featured-'.$suffix" label="Featured on the wall" hint="Hangs one size larger in the album." :checked="true" />
    </div>
    <x-form.upload :name="'portrait-'.$suffix" label="Portrait" image hint="JPEG, PNG or WebP up to 10 MB." accept="image/*" />
    <x-form.editor :name="'bio-'.$suffix" label="Biography" :value="'<h2>Early years</h2><p>Born in <strong>Omdurman</strong>, he read agriculture at the <a href=\'https://example.org\'>University of Khartoum</a>.</p><blockquote><p>Knowledge is the light we pass on.</p></blockquote>'" hint="Headings, bold, italic, links, lists and quotes." />
</div>
