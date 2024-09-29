<div>
    <!--sidenav -->
    <div class="fixed left-0 top-0 w-64 h-full bg-[#f8f4f3] p-4 z-50 sidebar-menu transition-transform">
        <a href="" class="flex items-center pb-4 border-b border-b-gray-800">
            <h2 class="font-bold text-2xl">DVUS<span class="bg-[#1e90ff]   text-white px-1 rounded-md">UNAH</span></h2>
        </a>
        <ul class="mt-4">
            {{ $slot }}
        </ul>
    </div>
    <div class="fixed top-0 left-0 w-full h-full bg-black/50 z-40 md:hidden sidebar-overlay"></div>
    <!-- end sidenav -->
</div>
