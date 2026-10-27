{{-- The application fields shared by the visitor and signed-in forms: affiliation, field, reason, agreements and the honeypot. --}}
@use('App\Http\Requests\CommunitySignupRequest')
<x-form.input name="affiliation" :label="__('Affiliation')" required autocomplete="organization" :hint="__('Your university, institute or organisation.')" />
<x-form.input name="academic_field" :label="__('Academic field')" required :hint="__('For example soil science or rural development.')" />
<x-form.textarea name="reason_for_joining" :label="__('Why you would like to join')" required :maxlength="CommunitySignupRequest::REASON_MAX_LENGTH" rows="6" />

{{-- Honeypot: hidden from people, filled by bots, never validated. --}}
<div class="form-honeypot" aria-hidden="true">
    <label for="{{ CommunitySignupRequest::HONEYPOT }}">{{ __('Website') }}</label>
    <input type="text" id="{{ CommunitySignupRequest::HONEYPOT }}" name="{{ CommunitySignupRequest::HONEYPOT }}" tabindex="-1" autocomplete="off" value="">
</div>

<div class="space-y-2">
    <x-form.checkbox name="conduct_agreed" required>{{ $copy['conductAgreement'] }}</x-form.checkbox>
    <x-form.checkbox name="privacy_agreed" required>{{ $copy['privacyAgreement'] }} <a href="#privacy" class="link-hairline !py-0">{{ __('Read the privacy note') }}</a>.</x-form.checkbox>
</div>
