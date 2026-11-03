{{-- Admin › Pages: the public pages whose words and images editors change, in site order. --}}
<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="__('Pages')" :intro="__('The fixed words and images of each public page: headings, introductions, buttons, empty-page lines, the community rules, the error pages, the header and footer, and how each page appears in search and when shared. Records such as publications and tributes have their own screens.')" />
    <x-admin.flash />

    <x-admin.list-toolbar :toolbar="$toolbar" :items="__('pages')" :placeholder="__('Search pages')" :table="$table" />

    <x-admin.list-results :density="$toolbar['density']">
        <x-admin.data-table :table="$table" data-pages-table>
            <x-slot:empty>
                <x-admin.list-empty :title="__('Pages')" text="" :filtered="true" />
            </x-slot:empty>
        </x-admin.data-table>
    </x-admin.list-results>
</div>
