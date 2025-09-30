<!DOCTYPE html>
<html class="scroll-smooth overflow-x-hidden" lang="en">
  <head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <link rel="icon" href="{{ asset('auth/assets/images/icons/icon-favicon.svg') }}" type="image/x-icon" sizes="16x16"> 
    <link rel="stylesheet" href="{{ asset('auth/assets/styles/tailwind.min.css?v=5.0') }}">
    <link rel="stylesheet" href="{{ asset('auth/assets/styles/style.min.css?v=5.0') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chivo:wght@400;700;900&amp;family=Noto+Sans:wght@400;500;600;700;800&amp;display=swap">
  </head>
<body class="w-screen relative overflow-x-hidden min-h-screen bg-gray-100 scrollbar-hide authentication-sign-in-page dark:bg-[#000]">
    <div class="wrapper mx-auto text-gray-900 font-normal grid scrollbar-hide grid-cols-[257px,1fr] grid-rows-[auto,1fr]" id="layout">
   {{--
    <aside class="bg-white row-span-2 border-r border-neutral relative flex flex-col justify-between p-[25px] dark:bg-dark-neutral-bg dark:border-dark-neutral-border"> 
        <div class="absolute p-2 border-neutral right-0 border bg-white rounded-full cursor-pointer duration-300 translate-x-1/2 hover:opacity-75 dark:bg-dark-neutral-bg dark:border-dark-neutral-border" id="sidebar-btn"><img src="assets/images/icons/icon-arrow-left.svg" alt="left chevron icon"></div>
        <div><a class="mb-10" href="index.html"> <img class="logo-maximize" src="assets/images/icons/icon-logo.svg" alt="Frox logo"><img class="logo-minimize ml-[10px]" src="assets/images/icons/icon-favicon.svg" alt="Frox logo"></a>
          <div class="pt-[106px] lg:pt-[35px] pb-[18px]">
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="dashboard" name="sidemenu" id="dashboard">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent active" for="dashboard">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-favorite-chart.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Dashboard</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="index.html">Ecommerce</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="finance-dashboard.html">Finance</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="jobs-dashboard.html">Jobs</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="cms-dashboard.html">CMS</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="crm-dashboard.html">CRM</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="analytics-dashboard-1.html">Analytics</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="project-dashboard.html">Project Manage</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="chat-page-1.html">Chat / Message</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="social-feed-1.html">Social Network</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="crypto-dashboard.html">Crypto</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="mailbox-inbox.html">Mailbox</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="file-manage-dashboard.html">File Manage</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="help-center-dashboard.html">Help Center</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="course-dashboard.html">Course Online</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="ecommerce" name="sidemenu" id="ecommerce">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="ecommerce">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-products.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Ecommerce</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="index.html">Dashboards</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="product-list.html">Products List</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="product-grid.html">Products Grid</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="product-details.html">Product Details</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="order-list.html">Order List</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="order-details.html">Order Details</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="transactions-list.html">Transactions</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="customers-lists.html">Customers List</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="reviews-list.html">Customers Review</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="seller-details.html">Seller Details</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="finance" name="sidemenu" id="finance">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="finance">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-wallet.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Finance</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="finance-dashboard.html">Dashboard</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="finance-cards.html">Cards</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="finance-transactions.html">Transactions</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="#" id="transaction-detail">Transactions Details</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="jobs" name="sidemenu" id="jobs">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="jobs">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-job.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Jobs</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="jobs-dashboard.html">Dashboard 1</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="jobs-dashboard.html">Dashboard 2</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="jobs-listing-1.html">Jobs Listing 1</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="jobs-listing-2.html">Jobs Listing 2</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="jobs-add-new-job.html">Add New Job</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="cms" name="sidemenu" id="cms">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="cms">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-cms.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">CMS</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="cms-dashboard.html">Dashboard</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="cms-post-listing-grid.html">Posts Grid</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="cms-post-listing-list.html">Post List</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="cms-media.html">Media</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="cms-add-post.html"> Add Post</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="cms-comment.html">Comments</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="crm" name="sidemenu" id="crm">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="crm">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-crm.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">CRM</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="crm-dashboard.html">Dashboard</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="crm-events.html">Events</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="crm-customers.html">Customers</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="crm-customer-details.html">Customer Details</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="crm-customer-edit.html"> Customer Edit</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="analytics" name="sidemenu" id="analytics">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="analytics">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-analytics.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Analytics</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="analytics-dashboard-1.html">Dashboard-1</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="analytics-dashboard-2.html">Dashboard-2</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="project-manage" name="sidemenu" id="project-manage">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="project-manage">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-project.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Project Manage</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="project-dashboard.html">Dashboard</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75 show-add-project"><a class="text-normal" href="#">Add Project</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="#" id="show-share-modal">Share Project</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="chat" name="sidemenu" id="chat">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="chat">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-chat.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Chat / Message</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="chat-page-1.html">Layout 1</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="chat-page-2.html">Layout 2</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="chat-page-3.html">Layout 3</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="network" name="sidemenu" id="network">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="network">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-network.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Social Network</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="social-feed-1.html">Social Feed 1</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="social-feed-2.html">Social Feed 2</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="social-feed-3.html">Social Feed 3</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="crypto" name="sidemenu" id="crypto">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="crypto">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-crypto.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Crypto</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="crypto-dashboard.html">Dashboard</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="mailbox" name="sidemenu" id="mailbox">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="mailbox">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-mailbox.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Mailbox</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="mailbox-inbox.html">Inbox</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="mailbox-read.html">Read</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="mailbox-chat.html">Chat</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="#" id="show-mail-modal">Compose</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="course" name="sidemenu" id="course">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="course">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-course.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Online Course</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="course-dashboard.html">Dashboard</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="course-course.html">Course</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="course-add-course.html">Add Course</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="file" name="sidemenu" id="file">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="file">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-file.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">File Manager</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="file-manage-dashboard.html">Dashboard</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="file-manage-folder.html">Folder</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="file-manage-folder-blank.html">Folder Blank</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="help" name="sidemenu" id="help">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="help">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-headphone.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Help Center</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="help-center-dashboard.html">Dashboard</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="help-center-ticket.html">Ticket</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="help-center-add-category.html">Add Category</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="help-center-knowledge-base.html">Knowledge Base</a></li>
                </ul>
              </div>
            </div>
            <div class="sidemenu-item rounded-xl relative">
              <input class="sr-only peer" type="checkbox" value="auth" name="sidemenu" id="auth">
              <label class="flex items-center justify-between w-full cursor-pointer py-[17px] px-[21px] focus:outline-none peer-checked:border-transparent" for="auth">
                <div class="flex items-center gap-[10px]"><img src="assets/images/icons/icon-auth.svg" alt="side menu icon"><span class="text-normal font-semibold text-gray-500 sidemenu-title dark:text-gray-dark-500">Authentication</span></div>
              </label><img class="absolute right-2 transition-all duration-150 caret-icon pointer-events-none peer-checked:rotate-180 top-[22px]" src="assets/images/icons/icon-arrow-down.svg" alt="caret icon">
              <div class="hidden peer-checked:block">
                <ul class="text-gray-300 child-menu z-10 pl-[53px]">
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="sign-in.html">Sign In</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="sign-up.html">Sign Up</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="reset-password.html">Reset password</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="password-required.html">Password required</a></li>
                  <li class="pb-2 transition-opacity duration-150 hover:opacity-75"><a class="text-normal" href="sign-up-success.html">Signup success</a></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="category-list">
            <div class="w-full bg-neutral h-[1px] mb-[21px] dark:bg-dark-neutral-border"></div>
            <h3 class="text-sm font-bold text-gray-1100 py-3 px-6 dark:text-gray-dark-1100">Categories</h3>
            <div><a class="flex items-center justify-between py-3 pl-6" href="#"><span class="text-gray-500 text-normal dark:text-gray-dark-500">Laptops</span>
                <div class="grid place-items-center rounded w-[18px] h-[18px] bg-yellow"> 
                  <p class="font-medium text-gray-1100 text-[11px] leading-[11px]">8</p>
                </div></a><a class="flex items-center justify-between py-3 pl-6" href="#"><span class="text-gray-500 text-normal dark:text-gray-dark-500">Mobile phones</span>
                <div class="grid place-items-center rounded w-[18px] h-[18px] bg-orange"> 
                  <p class="font-medium text-gray-1100 text-[11px] leading-[11px]">6</p>
                </div></a><a class="flex items-center justify-between py-3 pl-6" href="#"><span class="text-gray-500 text-normal dark:text-gray-dark-500">Desktops</span></a><a class="flex items-center justify-between py-3 pl-6" href="#"><span class="text-gray-500 text-normal dark:text-gray-dark-500">Accessories</span>
                <div class="grid place-items-center rounded w-[18px] h-[18px] bg-pink"> 
                  <p class="font-medium text-gray-1100 text-[11px] leading-[11px]">5</p>
                </div></a><a class="flex items-center justify-between py-3 pl-6" href="#"><span class="text-gray-500 text-normal dark:text-gray-dark-500">Portable storage</span>
                <div class="grid place-items-center rounded w-[18px] h-[18px] bg-green"> 
                  <p class="font-medium text-gray-1100 text-[11px] leading-[11px]">9</p>
                </div></a><a class="flex items-center justify-between py-3 pl-6" href="#"><span class="text-gray-500 text-normal dark:text-gray-dark-500">Networking</span></a>
            </div>
            <div class="flex items-center gap-3 py-3 px-6 mb-[22px]"><img src="assets/images/icons/icon-add-square.svg" alt="add icon">
              <p class="text-sm font-bold text-gray-1100 dark:text-gray-dark-1100">Add Category</p>
            </div>
          </div>
          <div class="w-full bg-neutral h-[1px] mb-[35px] dark:bg-dark-neutral-border"></div>
          <div class="pl-6 seller-maximize mb-[35px]"> 
            <h3 class="text-sm font-bold text-gray-1100 mb-[15px] dark:text-gray-dark-1100">Top Sellers</h3>
            <div class="flex items-center"><a class="block rounded-full border-neutral overflow-hidden border-[1.4px] dark:border-gray-dark-100 w-9 h-9 z-50" href="seller-details.html"><img class="w-full h-full object-cover" src="assets/images/avatar-layouts-1.png" alt="user avatar"></a><a class="block rounded-full border-neutral overflow-hidden border-[1.4px] dark:border-gray-dark-100 w-9 h-9 z-40 translate-x-[-10px]" href="seller-details.html"><img class="w-full h-full object-cover" src="assets/images/avatar-layouts-2.png" alt="user avatar"></a><a class="block rounded-full border-neutral overflow-hidden border-[1.4px] dark:border-gray-dark-100 w-9 h-9 z-30 translate-x-[-20px]" href="seller-details.html"><img class="w-full h-full object-cover" src="assets/images/avatar-layouts-3.png" alt="user avatar"></a><a class="block rounded-full border-neutral overflow-hidden border-[1.4px] dark:border-gray-dark-100 w-9 h-9 z-20 translate-x-[-30px]" href="seller-details.html"><img class="w-full h-full object-cover" src="assets/images/avatar-layouts-4.png" alt="user avatar"></a>
              <button class="w-9 h-9 rounded-full border-neutral overflow-hidden bg-color-brands grid place-items-center border-[1.4px] dark:border-dark-neutral-border z-10 translate-x-[-40px]" type="button"><img src="assets/images/icons/icon-add-circle.svg" alt="plus icon"></button>
            </div>
          </div>
          <div class="seller-minimize"> 
            <h3 class="text-sm font-bold text-gray-1100 mb-[15px] dark:text-gray-dark-1100">Sellers</h3>
            <div class="flex items-center round text-"><a class="block rounded-full border-neutral overflow-hidden border-[1.4px] dark:border-gray-dark-100 w-9 h-9 z-50" href="seller-details.html"><img class="w-full h-full object-cover" src="assets/images/avatar-layouts-1.png" alt="user avatar"></a><a class="block rounded-full border-neutral overflow-hidden border-[1.4px] dark:border-gray-dark-100 w-9 h-9 z-40 translate-x-[-10px]" href="seller-details.html"><img class="w-full h-full object-cover" src="assets/images/avatar-layouts-2.png" alt="user avatar"></a>
            </div>
          </div>
          <div class="upgrade-card">
            <div class="w-full bg-neutral h-[1px] mb-[35px] dark:bg-dark-neutral-border"></div>
            <div class="rounded-xl bg-neutral py-5 w-fit flex flex-col gap-5 justify-between px-[31px] dark:bg-dark-neutral-border">
              <div class="relative mr-[18px]"><img class="ml-[6px] dark:hidden" src="assets/images/icons/icon-chat-bubble.svg" alt="chat bubble"><img class="hidden ml-[6px] dark:block" src="assets/images/icons/icon-chat-bubble-dark.svg" alt="chat bubble"><img class="w-full h-full object-cover" src="assets/images/hero-layouts.svg" alt="hero"><img class="ml-auto absolute right-0 translate-x-[120%]" src="assets/images/circle-list-1.png" alt="circle list"></div>
              <p class="text-desc text-center text-gray-1100 font-normal mx-auto max-w-[15ch] dark:text-gray-dark-1100">Unlock more information now by Upgrade to<span class="font-bold">&nbsp;PRO</span></p>
              <button class="btn normal-case h-fit min-h-fit transition-all duration-300 border-4 bg-color-brands hover:bg-color-brands hover:border-[#B2A7FF] dark:hover:border-[#B2A7FF] px-5 block border-neutral py-[7px] dark:border-dark-neutral-border">Upgrade Now</button>
            </div>
          </div>
        </div>
        <div class="rounded-xl bg-neutral pt-4 flex items-center gap-5 mt-5 sidebar-control pr-[18px] pb-[13px] pl-[19px] dark:bg-dark-neutral-border">
          <div class="flex items-center gap-3"><i class="moon-icon" id="theme-toggle-dark-icon"><img class="cursor-pointer" src="assets/images/icons/icon-moon.svg" alt="moon icon"><img class="cursor-pointer" src="assets/images/icons/icon-moon-active.svg" alt="moon icon"></i>
            <label class="flex items-center cursor-pointer" for="theme-toggle" id="toggle-theme-btn"> 
              <div class="relative"> 
                <input class="sr-only peer" type="checkbox" name="" id="theme-toggle">
                <div class="block rounded-full w-[48px] h-[16px] bg-gray-300 peer-checked:bg-[#B2A7FF]"></div>
                <div class="dot dotS absolute rounded-full transition h-[24px] w-[24px] top-[-4px] left-[-4px] bg-[#B2A7FF] peer-checked:bg-color-brands"></div>
              </div>
            </label><i class="sun-icon" id="theme-toggle-light-icon"><img class="cursor-pointer" src="assets/images/icons/icon-sun.svg" alt="sun icon"><img class="cursor-pointer" src="assets/images/icons/icon-sun-active.svg" alt="sun icon"></i>
          </div>
          <div class="bg-neutral-bg w-[2px] h-[30px] dark:bg-dark-neutral-bg"></div>
          <div> <img class="cursor-pointer" id="sidebar-expand" src="assets/images/icons/icon-maximize-3.svg" alt="expand icon"></div>
        </div>
    </aside> --}}

    <header class="flex items-center justify-between flex-wrap bg-neutral-bg p-5 gap-5 md:py-6 md:pl-[25px] md:pr-[38px] lg:flex-nowrap dark:bg-dark-neutral-bg lg:gap-0">
        <a class="hidden logo" href="/">
            <img class="md:mr-[100px] lg:mr-[133px]" src="{{ asset('img/logo.png') }}" alt="логотип"></a>
        <div class="bg-gray-100 flex rounded-xl w-full m-0 py-[14px] px-[18px] xl:w-[360px] dark:bg-gray-dark-100 lg:max-w-[250px] xl:max-w-[360px] lg:mr-[47px] lg:ml-6 order-last lg:order-first">
            <img src="{{ asset('auth/assets/images/icons/icon-search-normal.svg') }}" alt="seacrh icon">
          <input class="input w-full bg-transparent outline-none pl-2 h-5 text-gray-300 focus:!outline-none placeholder:text-gray-900 dark:placeholder:text-gray-dark-300 placeholder:font-semibold" type="text" placeholder="Я ищу ...">
    <!--      <img src="{{ asset('auth/assets/images/icons/icon-microphone-2.svg') }}" alt="microphone icon"> -->
        </div>
    </header>

    <main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    @yield('content') 
        
    
    
        <footer class="mt-[37px]">
          <div class="w-full bg-neutral h-[1px] dark:bg-dark-neutral-border mb-[25px]"></div>
          <div class="flex items-center justify-between text-desc text-gray-400 flex-wrap gap-5 dark:text-gray-dark-400">
            <div class="flex items-center gap-2 flex-wrap">
              <p> <span>© 2023 -</span><span class="text-color-brands">&nbsp;Frox</span><span>&nbsp;Dashboard</span></p>
              <div class="bg-color-brands rounded-full hidden w-[2px] h-[2px] md:block"></div>
              <p> <span>Made by</span><a class="text-color-brands" href="https://alithemes.com" target="_blank">&nbsp;AliThemes</a></p>
            </div>
            <div class="flex items-center gap-[15px]"><a class="transition-colors duration-300 hover:text-color-brands" href="#">About</a><a class="transition-colors duration-300 hover:text-color-brands" href="#">Careers</a><a class="transition-colors duration-300 hover:text-color-brands" href="#">Policy</a><a class="transition-colors duration-300 hover:text-color-brands" href="#">Contact</a></div>
          </div>
        </footer>
    </main>

    </div>
    <script type="text/javascript" src="{{ asset('auth/assets/scripts/vendors/jquery-3.6.0.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('auth/assets/scripts/chart-utils.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('auth/assets/scripts/chart.min.js') }}"></script>
    <script type="text/javascript" src="https://unpkg.com/chartjs-chart-geo@3"></script>
    <script src="{{ asset('auth/assets/scripts/app.js?v=5.0') }}"></script>
  </body>
</html>
