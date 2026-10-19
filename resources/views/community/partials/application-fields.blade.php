{{-- The application fields shared by the visitor and signed-in forms: affiliation, field, reason, agreements and the honeypot. --}}
@use('App\Http\Requests\CommunitySignupRequest')
<x-input name="affiliation" label="Affiliation" required autocomplete="organization" hint="Your university, institute or organisation." />
<x-input name="academic_field" label="Academic field" required hint="For example soil science or rural development." />
<x-textarea name="reason_for_joining" label="Why you would like to join" required :maxlength="CommunitySignupRequest::REASON_MAX_LENGTH" rows="6" />

{{-- Honeypot: hidden from people, filled by bots, never validated. --}}
<div class="form-honeypot" aria-hidden="true">
    <label for="{{ CommunitySignupRequest::HONEYPOT }}">Website</label>
    <input type="text" id="{{ CommunitySignupRequest::HONEYPOT }}" name="{{ CommunitySignupRequest::HONEYPOT }}" tabindex="-1" autocomplete="off" value="">
</div>

<div class="space-y-4">
    <div>
        <label class="flex items-start gap-3 text-base font-light text-ash">
            <input type="checkbox" name="conduct_agreed" value="1" required class="mt-1.5 size-4 shrink-0 rounded-sm border-stone accent-evergreen" @checked(old('conduct_agreed'))>
            <span>{{ $copy['conductAgreement'] }}</span>
        </label>
        @error('conduct_agreed')
            <p class="mt-2 text-sm text-bronze" role="alert">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="flex items-start gap-3 text-base font-light text-ash">
            <input type="checkbox" name="privacy_agreed" value="1" required class="mt-1.5 size-4 shrink-0 rounded-sm border-stone accent-evergreen" @checked(old('privacy_agreed'))>
            <span>{{ $copy['privacyAgreement'] }} <a href="#privacy" class="text-evergreen underline decoration-brass underline-offset-4">Read the privacy note</a>.</span>
        </label>
        @error('privacy_agreed')
            <p class="mt-2 text-sm text-bronze" role="alert">{{ $message }}</p>
        @enderror
    </div>
</div>
