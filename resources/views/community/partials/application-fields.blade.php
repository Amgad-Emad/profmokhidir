{{-- The application fields shared by the visitor and signed-in forms: affiliation, field, reason, agreements and the honeypot. --}}
@use('App\Http\Requests\CommunitySignupRequest')
<x-form.input name="affiliation" label="Affiliation" required autocomplete="organization" hint="Your university, institute or organisation." />
<x-form.input name="academic_field" label="Academic field" required hint="For example soil science or rural development." />
<x-form.textarea name="reason_for_joining" label="Why you would like to join" required :maxlength="CommunitySignupRequest::REASON_MAX_LENGTH" rows="6" />

{{-- Honeypot: hidden from people, filled by bots, never validated. --}}
<div class="form-honeypot" aria-hidden="true">
    <label for="{{ CommunitySignupRequest::HONEYPOT }}">Website</label>
    <input type="text" id="{{ CommunitySignupRequest::HONEYPOT }}" name="{{ CommunitySignupRequest::HONEYPOT }}" tabindex="-1" autocomplete="off" value="">
</div>

<div class="space-y-2">
    <x-form.checkbox name="conduct_agreed" required>{{ $copy['conductAgreement'] }}</x-form.checkbox>
    <x-form.checkbox name="privacy_agreed" required>{{ $copy['privacyAgreement'] }} <a href="#privacy" class="link-hairline !py-0">Read the privacy note</a>.</x-form.checkbox>
</div>
