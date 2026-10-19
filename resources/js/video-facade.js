/*
 | Video facade. Registered as the Alpine component `videoFacade` on the player
 | frame (resources/views/components/video-player.blade.php). The page shows our
 | own poster and a play control; the third-party iframe (youtube-nocookie.com or
 | Vimeo with dnt=1) exists only inside a <template> until play is pressed, so
 | nothing is requested from the host on page view.
 */

export default function videoFacade() {
    return {
        playing: false,

        play() {
            this.playing = true;
            this.$nextTick(() => this.$refs.frame?.focus());
        },
    };
}
