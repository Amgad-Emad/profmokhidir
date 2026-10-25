/*
 | Section 2, part 2: joining the Scientific Community (apply, verify, wait,
 | approval, first visit) and an approved member's use of the chat.
 */
import journey from './journey.mjs';

const APPLICANT = {
    title: '2.3 Community applicant: from application to first visit',
    persona: 'Community applicant (a researcher who studied under Professor Khidir); then an administrator; then the same person as an approved member',
    goal: 'Join the Scientific Community and take part in the members\' conversation.',
    start: 'The Scientific Community page, under Community in the menu.',
    steps: [
        { text: 'The page explains who may join, the conduct rules and how their details are used. The applicant fills in their name, email, a password, their affiliation, academic field and why they would like to join, and agrees to the conduct rules and the privacy note.', fig: { id: 'applicant-01-form', caption: 'The application form, desktop' } },
        { text: 'After "Apply to join" they are signed in and the page thanks them, asks them to confirm their email address, and shows "Pending review".', fig: { id: 'applicant-02-submitted', caption: 'Application received, desktop' } },
        { text: 'Until the address is confirmed, members\' pages ask them to confirm it first, and offer to send the link again.', fig: { id: 'applicant-03-verify-email', caption: 'Confirm your email address, desktop' } },
        { text: 'They open the link in the email. The Community page now shows that the application is being reviewed. Trying to open the chat brings them back here.', fig: { id: 'applicant-04-pending', caption: 'Pending review, desktop' } },
        { text: 'An administrator sees "Community applications pending" on the dashboard and opens the list. Each application shows the affiliation, academic field and reason for joining.', fig: { id: 'admin-05-applications', caption: 'Applications waiting for review, desktop' } },
        { text: 'The administrator chooses "Approve". A dialog says what will happen and offers an optional note for the email. They confirm.', fig: { id: 'admin-06-approve-dialog', caption: 'Approving an application, desktop' } },
        { text: 'The administrator is told the applicant has been approved and emailed; the application moves to the Members tab.', fig: { id: 'admin-07-approved', caption: 'Application approved, desktop' } },
        { text: 'When the new member next opens the Community page it says "Welcome to the community", with an "Open the chat" button.', fig: { id: 'applicant-06-approved', caption: 'The member\'s welcome, desktop' } },
        { text: 'Their first visit to the room: the conversation so far, the room\'s purpose and conduct rules in the side panel, and the message box at the bottom.', fig: { id: 'applicant-07-first-chat', caption: 'A new member\'s first visit to the chat, desktop' } },
        { text: 'For comparison: a rejected applicant and a suspended member see their status and how to get in touch, and cannot open the chat.', figs: Object.assign([
            { id: 'applicant-08-rejected', label: 'Rejected' },
            { id: 'applicant-09-suspended', label: 'Suspended' },
        ], { caption: 'What a rejected applicant and a suspended member see, desktop' }) },
    ],
    behind: [
        'The account is created as "pending" with no permissions. A link is emailed to confirm the address, and the staff notification address is told a new application has arrived.',
        'Only administrators can approve or reject. The decision, and the optional note, are emailed to the applicant and written to the activity log.',
        'Approval gives the Member role. Every chat page, every message sent and every file opened checks that the person is still an approved member, so a suspension takes effect at once.',
        'The application form is protected against automated sign-ups and limited to a few attempts an hour.',
    ],
    result: 'The applicant became a member only after an administrator approved them, and could not see any chat content before that.',
};

const MEMBER = {
    title: '2.4 Approved member: taking part in the chat',
    persona: 'Approved community member',
    goal: 'Answer a colleague\'s question, share a chart and notes, report an inappropriate post, then manage their account and sign out.',
    start: 'The "Open the chat" link on the Community page (or the account menu).',
    steps: [
        { text: 'The member opens the room. Messages are grouped by day with a "New" line where they left off; the side panel holds the room\'s purpose, the conduct rules, pinned messages and the member list.', fig: { id: 'member-01-chat', caption: 'The Scientific Community room, desktop' } },
        { text: 'A colleague has asked for the 1988 rainfall series. The member points at the question, chooses "Reply", and writes their answer. The question is quoted above the message box.', fig: { id: 'member-02-reply', caption: 'Replying to a message, desktop' } },
        { text: 'They press Enter. The reply appears in the conversation with the original question quoted above it.', fig: { id: 'member-03-reply-sent', caption: 'The reply in the conversation, desktop' } },
        { text: 'They attach a chart image and a PDF with the paperclip (dragging files in also works). Each file shows its size, and the image asks for a short description for people who cannot see it.', fig: { id: 'member-04-attachments', caption: 'A picture and a PDF ready to send, desktop' } },
        { text: 'The message is sent with the picture shown in the conversation and the PDF offered as a download.', fig: { id: 'member-05-attachments-sent', caption: 'The message with its attachments, desktop' } },
        { text: 'Another post shares a colleague\'s unpublished thesis data. The member chooses "Report", explains why, and sends the report.', fig: { id: 'member-06-report', caption: 'Reporting a message, desktop' } },
        { text: 'The chat confirms: "Thank you. An administrator will look at this message."', fig: { id: 'member-07-reported', caption: 'Report sent, desktop' } },
        { text: 'From the account menu they open "Your account": name and email, password, appearance, two-factor sign-in, and signing out.', fig: { id: 'member-08-account', caption: 'Your account, desktop' } },
        { text: 'They choose "Sign out" and return to the public home page with a confirmation.', fig: { id: 'member-09-signed-out', caption: 'Signed out, desktop' } },
        { text: 'The room works the same way on a phone and in the dark appearance.', figs: Object.assign([
            { id: 'mobile-05-chat', label: 'Phone (390 px)' },
        ], { caption: 'The chat on a phone' }) },
        { text: 'The dark appearance of the room on a desktop.', fig: { id: 'member-10-chat-dark', caption: 'The chat in the dark appearance, desktop' } },
    ],
    behind: [
        'New messages from others appear within about five seconds without reloading the page.',
        'Attachments are stored privately and are served only to signed-in members and administrators, through links that expire. Photos are re-saved without their location data.',
        'Up to five files per message; pictures up to 10 MB and documents up to 25 MB by default (administrators can change both limits, or switch uploads off).',
        'A report goes to the administrators\' "Chat reports" queue and appears on their dashboard.',
    ],
    result: 'The member answered a question, shared files safely, flagged a problem for the administrators, and signed out, without ever leaving the private room.',
};

export default function journeysCommunity() {
    return [...journey(APPLICANT), ...journey(MEMBER)];
}
