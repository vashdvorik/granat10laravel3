<!doctype html>
<html lang="en" dir="ltr">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <title>HexaDash</title>

   <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">

   <!-- inject:css-->
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/bootstrap/bootstrap.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/fontawesome.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/footable.standalone.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/fullcalendar@5.2.0.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/jquery-jvectormap-2.0.5.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/jquery.mCustomScrollbar.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/leaflet.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/line-awesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/MarkerCluster.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/MarkerCluster.Default.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/slick.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/star-rating-svg.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/trumbowyg.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/wickedpicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/style.css') }}">

   <!-- endinject -->

   <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon.png') }}">

   <!-- Fonts -->
   <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

</head>

<body class="layout-light side-menu">
   <div class="mobile-search">
      <form action="/" class="search-form">
         <img src="{{ asset('cabinet/img/svg/search.svg') }}" alt="search" class="svg">
         <input class="form-control me-sm-2 box-shadow-none" type="search" placeholder="Search..." aria-label="Search">
      </form>
   </div>
   <div class="mobile-author-actions"></div>
   
   <header class="header-top">
      <nav class="navbar navbar-light">
         <div class="navbar-left">
            <div class="logo-area">
               <a class="navbar-brand" href="index.html">
                  <img class="dark" src="{{ asset('img/logo.png') }}" alt="logo">
                  <img class="light" src="{{ asset('img/logo.png') }}" alt="logo">
               </a>
               <a href="#" class="sidebar-toggle">
                  <img class="svg" src="{{ asset('cabinet/img/svg/align-center-alt.svg') }}" alt="img"></a>
            </div>
         </div>
         
         <!-- ends: navbar-left -->
         <div class="navbar-right">
            <ul class="navbar-right__menu">
               <li class="nav-search">
                  <a href="#" class="search-toggle">
                     <i class="uil uil-search"></i>
                     <i class="uil uil-times"></i>
                  </a>
                  <form action="/" class="search-form-topMenu">
                     <span class="search-icon uil uil-search"></span>
                     <input class="form-control me-sm-2 box-shadow-none" type="search" placeholder="Search..." aria-label="Search">
                  </form>
               </li>
               <li class="nav-message">
                  <div class="dropdown-custom">
                     <a href="javascript:;" class="nav-item-toggle icon-active">
                        <img class="svg" src="{{ asset('cabinet/img/svg/message.svg') }}" alt="img">
                     </a>
                     <div class="dropdown-parent-wrapper">
                        <div class="dropdown-wrapper">
                           <h2 class="dropdown-wrapper__title">Messages <span class="badge-circle badge-success ms-1">2</span></h2>
                           <ul>
                              <li class="author-online has-new-message">
                                 <div class="user-avater">
                                    <img src="{{ asset('cabinet/img/team-1.png') }}" alt="">
                                 </div>
                                 <div class="user-message">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">Web Design</a>
                                       <span class="time-posted">3 hrs ago</span>
                                    </p>
                                    <p>
                                       <span class="desc text-truncate" style="max-width: 215px;">Lorem
                                          ipsum
                                          dolor amet cosec Lorem ipsum</span>
                                       <span class="msg-count badge-circle badge-success badge-sm">1</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="author-offline has-new-message">
                                 <div class="user-avater">
                                    <img src="{{ asset('cabinet/img/team-1.png') }}" alt="">
                                 </div>
                                 <div class="user-message">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">Web Design</a>
                                       <span class="time-posted">3 hrs ago</span>
                                    </p>
                                    <p>
                                       <span class="desc text-truncate" style="max-width: 215px;">Lorem
                                          ipsum
                                          dolor amet cosec Lorem ipsum</span>
                                       <span class="msg-count badge-circle badge-success badge-sm">1</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="author-online has-new-message">
                                 <div class="user-avater">
                                    <img src="{{ asset('cabinet/img/team-1.png') }}" alt="">
                                 </div>
                                 <div class="user-message">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">Web Design</a>
                                       <span class="time-posted">3 hrs ago</span>
                                    </p>
                                    <p>
                                       <span class="desc text-truncate" style="max-width: 215px;">Lorem
                                          ipsum
                                          dolor amet cosec Lorem ipsum</span>
                                       <span class="msg-count badge-circle badge-success badge-sm">1</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="author-offline">
                                 <div class="user-avater">
                                    <img src="{{ asset('cabinet/img/team-1.png') }}" alt="">
                                 </div>
                                 <div class="user-message">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">Web Design</a>
                                       <span class="time-posted">3 hrs ago</span>
                                    </p>
                                    <p>
                                       <span class="desc text-truncate" style="max-width: 215px;">Lorem
                                          ipsum
                                          dolor amet cosec Lorem ipsum</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="author-offline">
                                 <div class="user-avater">
                                    <img src="{{ asset('cabinet/img/team-1.png') }}" alt="">
                                 </div>
                                 <div class="user-message">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">Web Design</a>
                                       <span class="time-posted">3 hrs ago</span>
                                    </p>
                                    <p>
                                       <span class="desc text-truncate" style="max-width: 215px;">Lorem
                                          ipsum
                                          dolor amet cosec Lorem ipsum</span>
                                    </p>
                                 </div>
                              </li>
                           </ul>
                           <a href="" class="dropdown-wrapper__more">See All Message</a>
                        </div>
                     </div>
                  </div>
               </li>
               <!-- ends: nav-message -->
               <li class="nav-notification">
                  <div class="dropdown-custom">
                     <a href="javascript:;" class="nav-item-toggle icon-active">
                        <img class="svg" src="{{ asset('cabinet/img/svg/alarm.svg') }}" alt="img">
                     </a>
                     <div class="dropdown-parent-wrapper">
                        <div class="dropdown-wrapper">
                           <h2 class="dropdown-wrapper__title">Notifications <span class="badge-circle badge-warning ms-1">4</span></h2>
                           <ul>
                              <li class="nav-notification__single nav-notification__single--unread d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--primary">
                                    <img class="svg" src="{{ asset('cabinet/img/svg/inbox.svg') }}" alt="inbox">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="nav-notification__single nav-notification__single--unread d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--secondary">
                                    <img class="svg" src="{{ asset('cabinet/img/svg/upload.svg') }}" alt="upload">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="nav-notification__single nav-notification__single--unread d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--success">
                                    <img class="svg" src="{{ asset('cabinet/img/svg/log-in.svg') }}" alt="log-in">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="nav-notification__single nav-notification__single d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--info">
                                    <img class="svg" src="{{ asset('cabinet/img/svg/at-sign.svg') }}" alt="at-sign">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="nav-notification__single nav-notification__single d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--danger">
                                    <img src="{{ asset('cabinet/img/svg/heart.svg') }}" alt="heart" class="svg">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                           </ul>
                           <a href="" class="dropdown-wrapper__more">See all incoming activity</a>
                        </div>
                     </div>
                  </div>
               </li>
               <!-- ends: .nav-notification -->
               <li class="nav-settings">
                  <div class="dropdown-custom">
                     <a href="javascript:;" class="nav-item-toggle">
                        <img src="{{ asset('cabinet/img/setting.png') }}" alt="setting">
                     </a>
                     <div class="dropdown-parent-wrapper">
                        <div class="dropdown-wrapper dropdown-wrapper--large">
                           <ul class="list-settings">
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/mail.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">All Features</a>
                                    </h6>
                                    <p>Introducing Increment subscriptions </p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/color-palette.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Themes</a>
                                    </h6>
                                    <p>Third party themes that are compatible</p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/home.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Payments</a>
                                    </h6>
                                    <p>We handle billions of dollars</p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/video-camera.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Design Mockups</a>
                                    </h6>
                                    <p>Share planning visuals with clients</p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/document.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Content Planner</a>
                                    </h6>
                                    <p>Centralize content gethering and editing</p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/microphone.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Diagram Maker</a>
                                    </h6>
                                    <p>Plan user flows & test scenarios</p>
                                 </div>
                              </li>
                           </ul>
                        </div>
                     </div>
                  </div>
               </li>
               <!-- ends: .nav-support -->
               <li class="nav-flag-select">
                  <div class="dropdown-custom">
                     <a href="javascript:;" class="nav-item-toggle"><img src="{{ asset('cabinet/img/flag.png') }}" alt="" class="rounded-circle"></a>
                     <div class="dropdown-parent-wrapper">
                        <div class="dropdown-wrapper dropdown-wrapper--small">
                           <a href=""><img src="{{ asset('cabinet/img/eng.png') }}" alt=""> English</a>
                           <a href=""><img src="{{ asset('cabinet/img/ger.png') }}" alt=""> German</a>
                           <a href=""><img src="{{ asset('cabinet/img/spa.png') }}" alt=""> Spanish</a>
                           <a href=""><img src="{{ asset('cabinet/img/tur.png') }}" alt=""> Turkish</a>
                           <a href=""><img src="{{ asset('cabinet/img/iraq.png') }}" alt=""> Arabic</a>
                        </div>
                     </div>

                  </div>
               </li>
               <!-- ends: .nav-flag-select -->
               <li class="nav-author">
                  <div class="dropdown-custom">
                     <a href="javascript:;" class="nav-item-toggle"><img src="{{ asset('cabinet/img/author-nav.jpg') }}" alt="" class="rounded-circle">
                        <span class="nav-item__title">Danial<i class="las la-angle-down nav-item__arrow"></i></span>
                     </a>
                     <div class="dropdown-parent-wrapper">
                        <div class="dropdown-wrapper">
                           <div class="nav-author__info">
                              <div class="author-img">
                                 <img src="{{ asset('cabinet/img/author-nav.jpg') }}" alt="" class="rounded-circle">
                              </div>
                              <div>
                                 <h6>Rabbi Islam Rony</h6>
                                 <span>UI Designer</span>
                              </div>
                           </div>
                           <div class="nav-author__options">
                              <ul>
                                 <li>
                                    <a href="">
                                       <i class="uil uil-user"></i> Profile</a>
                                 </li>
                                 <li>
                                    <a href="">
                                       <i class="uil uil-setting"></i>
                                       Settings</a>
                                 </li>
                                 <li>
                                    <a href="">
                                       <i class="uil uil-key-skeleton"></i> Billing</a>
                                 </li>
                                 <li>
                                    <a href="">
                                       <i class="uil uil-users-alt"></i> Activity</a>
                                 </li>
                                 <li>
                                    <a href="">
                                       <i class="uil uil-bell"></i> Help</a>
                                 </li>
                              </ul>

                              <a href="{{ route('logout') }}"
                                 onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                 class="nav-author__signout">
                                 <i class="uil uil-sign-out-alt"></i> Выйти
                              </a>
                              <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                 @csrf
                              </form>

                        <!--      <a href="" class="nav-author__signout">
                                 <i class="uil uil-sign-out-alt"></i> Sign Out</a> -->
                           </div>
                        </div>
                        <!-- ends: .dropdown-wrapper -->
                     </div>
                  </div>
               </li>
               <!-- ends: .nav-author -->
            </ul>
            <!-- ends: .navbar-right__menu -->
            <div class="navbar-right__mobileAction d-md-none">
               <a href="#" class="btn-search">
                  <img src="{{ asset('cabinet/img/svg/search.svg') }}" alt="search" class="svg feather-search">
                  <img src="{{ asset('cabinet/img/svg/x.svg') }}" alt="x" class="svg feather-x"></a>
               <a href="#" class="btn-author-action">
                  <img class="svg" src="{{ asset('cabinet/img/svg/more-vertical.svg') }}" alt="more-vertical"></a>
            </div>
         </div>
         <!-- ends: .navbar-right -->
      </nav>
   </header>

   
   <main class="main-content">

      <div class="sidebar-wrapper">
         
         <div class="sidebar sidebar-collapse" id="sidebar">
            <div class="sidebar__menu-group">
               <ul class="sidebar_nav">
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-envelope"></span>
                        <span class="menu-text">Email</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="inbox.html">Inbox</a>
                        </li>
                        <li class="">
                           <a href="read-email.html">Read
                              Email</a>
                        </li>
                     </ul>
                  </li>
                  <li>
                     <a href="chat.html" class="">
                        <span class="nav-icon uil uil-chat"></span>
                        <span class="menu-text">Chat</span>
                        <span class="badge badge-success menuItem rounded-circle">3</span>
                     </a>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-bag"></span>
                        <span class="menu-text text-initial">eCommerce</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="products.html">Products</a>
                        </li>
                        <li class="">
                           <a href="product-details.html">Product
                              Details</a>
                        </li>
                        <li class="">
                           <a href="add-product.html">Product
                              Add</a>
                        </li>
                        <li class="">
                           <a href="add-product.html">Product
                              Edit</a>
                        </li>
                        <li class="">
                           <a href="cart.html">Cart</a>
                        </li>
                        <li class="">
                           <a href="order.html">Orders</a>
                        </li>
                        <li class="">
                           <a href="sellers.html">Sellers</a>
                        </li>
                        <li class="">
                           <a href="invoice.html">Invoices</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-folder"></span>
                        <span class="menu-text">Project</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="projects.html">Project</a>
                        </li>
                        <li class="">
                           <a href="application-ui.html">Project
                              Details</a>
                        </li>
                        <li class="">
                           <a href="create.html">Create
                              Project</a>
                        </li>
                     </ul>
                  </li>
                  <li>
                     <a href="calendar.html" class="">
                        <span class="nav-icon uil uil-calendar-alt"></span>
                        <span class="menu-text">Calendar</span>
                     </a>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-users-alt"></span>
                        <span class="menu-text">Users</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="users-membar.html">Team</a>
                        </li>
                        <li class="">
                           <a href="users-card.html">Users Grid</a>
                        </li>
                        <li class="">
                           <a href="users-list.html">Users
                              List</a>
                        </li>
                        <li class="">
                           <a href="users-card2.html">Users Grid
                              style</a>
                        </li>
                        <li class="">
                           <a href="users-group.html">Users
                              Group</a>
                        </li>
                        <li class="">
                           <a href="user-info.html">Add User</a>
                        </li>
                        <li class="">
                           <a href="users-datatable.html">Users
                              Table</a>
                        </li>

                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-at"></span>
                        <span class="menu-text">Contact</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="contact-2.html">Contact
                              Grid</a>
                        </li>
                        <li class="">
                           <a href="contact-1.html">Contact
                              List</a>
                        </li>
                        <li class="">
                           <a href="contact-3.html">Contact
                              Create</a>
                        </li>
                     </ul>
                  </li>
                  <li class="">
                     <a href="note.html">
                        <span class="nav-icon uil uil-clipboard-notes"></span>
                        <span class="menu-text">Note</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="to-do.html">
                        <span class="nav-icon uil uil-check-square"></span>
                        <span class="menu-text">To-Do</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="kanban.html">
                        <span class="nav-icon uil uil-expand-arrows"></span>
                        <span class="menu-text">Kanban Board</span>

                     </a>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-exchange"></span>
                        <span class="menu-text">Import & Export</span>
                        <span class="toggle-icon"></span>

                     </a>
                     <ul>
                        <li class="">
                           <a href="import.html">Import</a>
                        </li>
                        <li class="">
                           <a href="export.html">Export</a>
                        </li>
                        <li class="">
                           <a href="export-selected.html">Export
                              Selected
                           </a>
                        </li>
                     </ul>
                  </li>
                  <li class="">
                     <a href="file-manager.html">
                        <span class="nav-icon uil uil-file"></span>
                        <span class="menu-text">File Manager</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="task-app.html">
                        <span class="nav-icon uil uil-lightbulb-alt"></span>
                        <span class="menu-text">Task App</span>

                     </a>
                  </li>
                  <li class="">
                     <a href="bookmarks.html">
                        <span class="nav-icon uil uil-bookmark"></span>
                        <span class="menu-text">Bookmarks</span>
                     </a>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-apps"></span>
                        <span class="menu-text">Social App</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="profile.html">My Profile</a>
                        </li>
                        <li class="">
                           <a href="profile-setting.html">Profile
                              Settings</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-books"></span>
                        <span class="menu-text">Learning App</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="course.html">Course</a>
                        </li>
                        <li class="">
                           <a href="course-details.html">Course
                              Details</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-user"></span>
                        <span class="menu-text">Support</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="support-ticket.html">Support
                              Ticket</a>
                        </li>
                        <li class="">
                           <a href="support-details.html">Ticket
                              Details</a>
                        </li>
                        <li class="">
                           <a href="new-ticket.html">New
                              Ticket</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-search"></span>
                        <span class="menu-text">JobSearch</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="jobSearch.html">Grid
                              view</a>
                        </li>
                        <li class="">
                           <a href="jobSearchList.html">List view</a>
                        </li>
                        <li class="">
                           <a href="jobDetails.html">Details</a>
                        </li>
                        <li class="">
                           <a href="job-apply.html">Apply</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-table"></span>
                        <span class="menu-text">Table</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="basic-table.html">Basic
                              Table</a>
                        </li>
                        <li class="">
                           <a href="datatable.html">Data
                              Table</a>
                        </li>
                        <li class="">
                           <a href="dynamic-table.html">
                              Dynamic Table
                           </a>
                        </li>
                     </ul>
                  </li>
                  <li class="menu-title mt-30">
                     <span>CRUD</span>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-database"></span>
                        <span class="menu-text">Firestore Crud</span>
                        <span class="toggle-icon"></span>

                     </a>
                     <ul>
                        <li class="">
                           <a href="firestore.html">View All</a>
                        </li>
                        <li class="">
                           <a href="firestore-add.html">Add
                              New</a>
                        </li>
                     </ul>
                  </li>
                  <li class="menu-title mt-30">
                     <span>Features</span>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-layers"></span>
                        <span class="menu-text">UI Elements</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="alert.html">Alert</a>
                        </li>
                        <li class="">
                           <a href="avatar.html">Avatar</a>
                        </li>
                        <li class="">
                           <a href="badge.html">Badge</a>
                        </li>
                        <li class="">
                           <a href="breadcrumbs.html">Breadcrumb</a>
                        </li>
                        <li class="" class="">
                           <a href="buttons.html">Button</a>
                        </li>
                        <li class="">
                           <a href="cards.html">Cards</a>
                        </li>
                        <li class="">
                           <a href="carousel.html">Carousel</a>
                        </li>
                        <li class="">
                           <a href="checkbox.html">Checkbox</a>
                        </li>
                        <li class="">
                           <a href="collapse.html">Collapse</a>
                        </li>
                        <li class="">
                           <a href="comments.html">Comments</a>
                        </li>
                        <li class="">
                           <a href="date-picker.html">DatePicker</a>
                        </li>
                        <li class="">
                           <a href="drawer.html">Drawer</a>
                        </li>
                        <li class="">
                           <a href="drag-drop.html">Drag &
                              Drop</a>
                        </li>
                        <li class="">
                           <a href="dropdown.html">Dropdown</a>
                        </li>
                        <li class="">
                           <a href="empty.html">Empty</a>
                        </li>
                        <li class="">
                           <a href="grid.html">Grid</a>
                        </li>
                        <li class="">
                           <a href="input.html">Input</a>
                        </li>
                        <li class="">
                           <a href="list.html">List</a>
                        </li>
                        <li class="">
                           <a href="menu.html">Menu</a>
                        </li>
                        <li class="">
                           <a href="message.html">Message</a>
                        </li>
                        <li class="">
                           <a href="modal.html">Modals</a>
                        </li>
                        <li class="">
                           <a href="notifications.html">Notification</a>
                        </li>
                        <li class="">
                           <a href="page-header.html">Page
                              Headers</a>
                        </li>
                        <li class="">
                           <a href="pagination.html">Paginations</a>
                        </li>
                        <li class="">
                           <a href="progressbar.html">Progress</a>
                        </li>
                        <li class="">
                           <a href="radio.html">Radio</a>
                        </li>
                        <li class="">
                           <a href="rate.html">Rate</a>
                        </li>
                        <li class="">
                           <a href="result.html">Result</a>
                        </li>
                        <li class="">
                           <a href="select.html">Select</a>
                        </li>
                        <li class="">
                           <a href="skeleton.html">Skeleton</a>
                        </li>
                        <li class="">
                           <a href="slider.html">Slider</a>
                        </li>
                        <li class="">
                           <a href="spin.html">Spinner</a>
                        </li>
                        <li class="">
                           <a href="statistics.html">Statistic</a>
                        </li>
                        <li class="">
                           <a href="steps.html">Steps</a>
                        </li>
                        <li class="">
                           <a href="switch.html">Switch</a>
                        </li>
                        <li class="">
                           <a href="tab.html">Tabs</a>
                        </li>
                        <li class="">
                           <a href="tag.html">Tags</a>
                        </li>
                        <li class="">
                           <a href="timeline.html">Timeline</a>
                        </li>
                        <li class="">
                           <a href="timeline-2.html">Timeline 2</a>
                        </li>
                        <li class="">
                           <a href="timeline-3.html">Timeline
                              3</a>
                        </li>
                        <li class="">
                           <a href="time-picker.html">Timepicker</a>
                        </li>
                        <li class="">
                           <a href="dashboard-base.html">Typography</a>
                        </li>
                        <li class="">
                           <a href="uploads.html">Upload</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-chart"></span>
                        <span class="menu-text">Charts</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="charts.html">Chart JS</a>
                        </li>
                        <li class="">
                           <a href="apexchart.html">
                              <span class="menu-text">Apexchart</span>
                              <span class="badge badge-success menuItem">New</span>
                           </a>
                        </li>
                        <li class="">
                           <a href="google-chart.html">Google
                              Charts</a>
                        </li>
                        <li class="">
                           <a href="peity-chart.html">Peity
                              Charts</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-keyhole-circle"></span>
                        <span class="menu-text">Froms</span>
                        <span class="toggle-icon"></span>

                     </a>
                     <ul>
                        <li class="">
                           <a href="form.html">Basics</a>
                        </li>
                        <li class="">
                           <a href="form-layouts.html">Layouts</a>
                        </li>
                        <li class="">
                           <a href="form-elements.html">Elements</a>
                        </li>
                        <li class="">
                           <a href="form-components.html">Components</a>
                        </li>
                        <li class="">
                           <a href="form-validations.html">Validations</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-server"></span>
                        <span class="menu-text">Widget</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="widget-charts.html">Chart</a>
                        </li>
                        <li class="">
                           <a href="widget-mixed.html">Mixed</a>
                        </li>
                        <li class="">
                           <a href="widget-card.html">Card</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-square"></span>
                        <span class="menu-text">Wizards</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="checkout-wizard6.html">Wizard
                              1</a>
                        </li>
                        <li class="">
                           <a href="checkout-wizard7.html">Wizard
                              2</a>
                        </li>
                        <li class="">
                           <a href="checkout-wizard8.html">Wizard
                              3</a>
                        </li>
                        <li class="">
                           <a href="checkout-wizard9.html">Wizard
                              4</a>
                        </li>
                        <li class="">
                           <a href="checkout-wizard10.html">Wizard
                              5</a>
                        </li>
                     </ul>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-grid"></span>
                        <span class="menu-text">Icons</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="unicon-icons.html">Unicon
                              Icons</a>
                        </li>
                        <li class="">
                           <a href="fontawesome.html">Font
                              Awesome</a>
                        </li>
                        <li class="">
                           <a href="lineawesome.html">Line
                              Awesome</a>
                        </li>
                     </ul>
                  </li>
                  <li>
                     <a href="editors.html" class="">
                        <span class="nav-icon uil uil-edit"></span>
                        <span class="menu-text">Editors</span>
                     </a>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-map"></span>
                        <span class="menu-text">Maps</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="google-map.html">Google
                              Maps</a>
                        </li>
                        <li class="">
                           <a href="leaflet-map.html">Leaflet
                              Maps</a>
                        </li>
                        <li class="">
                           <a href="vector-map.html">Vector
                              Maps</a>
                        </li>
                     </ul>
                  </li>
                  <li class="menu-title mt-30">
                     <span>Pages</span>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-images"></span>
                        <span class="menu-text">Gallery</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="gallery.html">Gallery 1</a>
                        </li>
                        <li class="">
                           <a href="gallery2.html">Gallery 2</a>
                        </li>
                     </ul>
                  </li>
                  <li>
                     <a href="pricing.html" class="">
                        <span class="nav-icon uil uil-bill"></span>
                        <span class="menu-text">Pricing</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="banner.html">
                        <span class="nav-icon uil uil-thumbs-up"></span>
                        <span class="menu-text">Banners</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="testimonial.html">
                        <span class="nav-icon uil uil-book-open"></span>
                        <span class="menu-text">Testimonial</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="faq.html">
                        <span class="nav-icon uil uil-question-circle"></span>
                        <span class="menu-text">FAQ's</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="search.html">
                        <span class="nav-icon uil uil-credit-card-search"></span>
                        <span class="menu-text">Search Results</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="blank.html">
                        <span class="nav-icon uil uil-circle"></span>
                        <span class="menu-text">Blank Page</span>
                     </a>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-window"></span>
                        <span class="menu-text">Knowledge Base</span>
                        <span class="toggle-icon"></span>

                     </a>
                     <ul>
                        <li class="">
                           <a href="knowledgebase.html">Knowledge
                              Base</a>
                        </li>
                        <li class="">
                           <a href="knowledgebase-2.html">All
                              Article</a>
                        </li>

                        <li class="">
                           <a href="knowledgebase-3.html">Single
                              Article</a>
                        </li>
                     </ul>
                  </li>
                  <li class="">
                     <a href="support.html">
                        <span class="nav-icon uil uil-headphones"></span>
                        <span class="menu-text">Support Center</span>
                     </a>
                  </li>
                  <li class="has-child">
                     <a href="#" class="">
                        <span class="nav-icon uil uil-images"></span>
                        <span class="menu-text">Blog</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="">
                           <a href="blog.html">style 1</a>
                        </li>
                        <li class="">
                           <a href="blog2.html">Style 2</a>
                        </li>
                        <li class="">
                           <a href="blog3.html">Style 3</a>
                        </li>
                        <li class="">
                           <a href="blog-details.html">Details</a>
                        </li>
                     </ul>
                  </li>
                  <li class="">
                     <a href="terms.html">
                        <span class="nav-icon uil uil-question-circle"></span>
                        <span class="menu-text">Terms & Conditions</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="maintenance.html">
                        <span class="nav-icon uil uil-airplay"></span>
                        <span class="menu-text">Maintenance</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="404.html">
                        <span class="nav-icon uil uil-exclamation-triangle"></span>
                        <span class="menu-text">404</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="coming-soon.html">
                        <span class="nav-icon uil uil-sync"></span>
                        <span class="menu-text">Coming Soon</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="login.html">
                        <span class="nav-icon uil uil-signin"></span>
                        <span class="menu-text">Log In</span>
                     </a>
                  </li>
                  <li class="">
                     <a href="sign-up.html">
                        <span class="nav-icon uil uil-sign-out-alt"></span>
                        <span class="menu-text">Sign Up</span>
                     </a>
                  </li> 
                  <li class="">
                  <a href="{{ route('logout') }}"
                     onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                     <span class="nav-icon uil uil-sign-out-alt"></span>
                     <span class="menu-text">Выйти</span>
                  </a>

                  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                     @csrf
                  </form>
                  </li>

               </ul>
            </div>
         </div>
      </div>
     
      <div class="contents">

         <div class="crm mb-25">
            <div class="container-fluid">
               <div class="row ">
                  <div class="col-lg-12">

                     <div class="breadcrumb-main">
                        <h4 class="text-capitalize breadcrumb-title">Demo 1</h4>
                        <div class="breadcrumb-action justify-content-center flex-wrap">
                           <nav aria-label="breadcrumb">
                              <ol class="breadcrumb">
                                 <li class="breadcrumb-item"><a href="#"><i class="uil uil-estate"></i>Dashboard</a></li>
                                 <li class="breadcrumb-item active" aria-current="page">Demo 1</li>
                              </ol>
                           </nav>
                        </div>
                     </div>

                  </div>
                  <div class="col-xxl-6">
                     <div class="row">
                        <div class="col-xxl-6 col-sm-6 mb-25">
                           <!-- Card 1  -->
                           <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">





                              <div class="overview-content w-100">
                                 <div class=" ap-po-details-content d-flex flex-wrap justify-content-between">
                                    <div class="ap-po-details__titlebar">
                                       <h1>100+</h1>
                                       <p>Total Products</p>
                                    </div>
                                    <div class="ap-po-details__icon-area">
                                       <div class="svg-icon order-bg-opacity-primary color-primary">

                                          <i class="uil uil-briefcase-alt"></i>
                                       </div>
                                    </div>
                                 </div>
                                 <div class="ap-po-details-time">
                                    <span class="color-success"><i class="las la-arrow-up"></i>
                                       <strong>25.36%</strong></span>
                                    <small>Since last month</small>
                                 </div>
                              </div>

                           </div>
                           <!-- Card 1 End  -->
                        </div>
                        <div class="col-xxl-6 col-sm-6 mb-25">
                           <!-- Card 2 -->
                           <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">





                              <div class="overview-content w-100">
                                 <div class=" ap-po-details-content d-flex flex-wrap justify-content-between">
                                    <div class="ap-po-details__titlebar">
                                       <h1>30,825</h1>
                                       <p>Total Orders</p>
                                    </div>
                                    <div class="ap-po-details__icon-area">
                                       <div class="svg-icon order-bg-opacity-info color-info">

                                          <i class="uil uil-shopping-cart-alt"></i>
                                       </div>
                                    </div>
                                 </div>
                                 <div class="ap-po-details-time">
                                    <span class="color-success"><i class="las la-arrow-up"></i>
                                       <strong>25.36%</strong></span>
                                    <small>Since last month</small>
                                 </div>
                              </div>

                           </div>
                           <!-- Card 2 End  -->
                        </div>
                        <div class="col-xxl-6 col-sm-6 mb-25">
                           <!-- Card 3 -->
                           <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">





                              <div class="overview-content w-100">
                                 <div class=" ap-po-details-content d-flex flex-wrap justify-content-between">
                                    <div class="ap-po-details__titlebar">
                                       <h1>$30,825</h1>
                                       <p>Total Sales</p>
                                    </div>
                                    <div class="ap-po-details__icon-area">
                                       <div class="svg-icon order-bg-opacity-secondary color-secondary">

                                          <i class="uil uil-usd-circle"></i>
                                       </div>
                                    </div>
                                 </div>
                                 <div class="ap-po-details-time">
                                    <span class="color-danger"><i class="las la-arrow-down"></i>
                                       <strong>25.36%</strong></span>
                                    <small>Since last month</small>
                                 </div>
                              </div>

                           </div>
                           <!-- Card 3 End  -->
                        </div>
                        <div class="col-xxl-6 col-sm-6 mb-25">
                           <!-- Card 4  -->
                           <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">





                              <div class="overview-content w-100">
                                 <div class=" ap-po-details-content d-flex flex-wrap justify-content-between">
                                    <div class="ap-po-details__titlebar">
                                       <h1>30,825</h1>
                                       <p>New Customers</p>
                                    </div>
                                    <div class="ap-po-details__icon-area">
                                       <div class="svg-icon order-bg-opacity-warning color-warning">

                                          <i class="uil uil-users-alt"></i>
                                       </div>
                                    </div>
                                 </div>
                                 <div class="ap-po-details-time">
                                    <span class="color-success"><i class="las la-arrow-up"></i>
                                       <strong>25.36%</strong></span>
                                    <small>Since last month</small>
                                 </div>
                              </div>

                           </div>
                           <!-- Card 4 End  -->
                        </div>
                     </div>
                  </div>
                  <div class="col-xxl-6 mb-25">
                     <div class="card border-0 h-100">
                        <div class="card-header border-0 pb-md-0 pb-20">
                           <h4>Sales Reports</h4>
                           <div class="card-extra">
                              <div class="dropdown dropleft">
                                 <a href="#" role="button" id="else" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img src="{{ asset('cabinet/img/svg/more-horizontal.svg') }}" alt="more-horizontal" class="svg">
                                 </a>
                                 <div class="dropdown-menu" aria-labelledby="else">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                 </div>
                              </div>
                           </div>
                        </div>
                        <div class="card-body pt-0 pb-25 mt-n10">
                           <div class="label-detailed">
                              <div class="label-detailed__single"><span class="label-detailed__type label-detailed__type--primary">Orders</span> <strong class="label-detailed__total">$8,550</strong> <span class="label-detailed__status color-success"><img class="svg" src="{{ asset('cabinet/img/svg/arrow-success-up.svg') }}" alt=""><strong>25%</strong></span> </div>
                              <div class="label-detailed__single"><span class="label-detailed__type label-detailed__type--info">Sales</span> <strong class="label-detailed__total">$5,550</strong> <span class="label-detailed__status color-danger"><img class="svg" src="{{ asset('cabinet/img/svg/arrow-danger-down.svg') }}" alt=""> <strong>15%</strong></span> </div>
                           </div>
                           <div class="parentContainer position-relative">


                              <div>
                                 <canvas id="salesReports"></canvas>
                              </div>


                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-xxl-4 mb-25">

                     <div class="card border-0 px-25 h-100">
                        <div class="card-header px-0 border-0">
                           <h6>Sales Growth</h6>
                           <div class="card-extra">
                              <ul class="card-tab-links nav-tabs nav" role="tablist">
                                 <li>
                                    <a class="active" href="#salesgrowth" data-bs-toggle="tab" id="salesgrowth-tab" role="tab" aria-selected="true">Today</a>
                                 </li>
                                 <li>
                                    <a href="#salesgrowth2" data-bs-toggle="tab" id="salesgrowth2-tab" role="tab" aria-selected="false">Week</a>
                                 </li>
                                 <li>
                                    <a href="#salesgrowth3" data-bs-toggle="tab" id="salesgrowth3-tab" role="tab" aria-selected="false">Month</a>
                                 </li>
                              </ul>
                           </div>
                        </div>
                        <div class="card-body p-0 pb-sm-25">
                           <div class="tab-content">
                              <div class="tab-pane active show" id="salesgrowth" role="tabpanel" aria-labelledby="salesgrowth-tab">
                                 <div class="label-detailed label-detailed--two">
                                    <div class="label-detailed__single"><strong class="label-detailed__total">$8,550</strong> <span class="label-detailed__status color-success"><img class="svg" src="{{ asset('cabinet/img/svg/arrow-success-up.svg') }}" alt=""> <strong>25%</strong></span> </div>
                                    <div class="label-detailed__single"><strong class="label-detailed__total">$5,550</strong> <span class="label-detailed__status color-danger"><img class="svg" src="{{ asset('cabinet/img/svg/arrow-danger-down.svg') }}" alt=""> <strong>15%</strong></span> </div>
                                 </div>
                                 <div class="parentContainer">


                                    <div>
                                       <canvas id="salesGrowthToday"></canvas>
                                    </div>


                                 </div>
                              </div>
                              <div class="tab-pane" id="salesgrowth2" role="tabpanel" aria-labelledby="salesgrowth2-tab">
                                 <div class="label-detailed label-detailed--two">
                                    <div class="label-detailed__single"><strong class="label-detailed__total">$8,550</strong> <span class="label-detailed__status color-success"><img class="svg" src="{{ asset('cabinet/img/svg/arrow-success-up.svg') }}" alt=""> <strong>25%</strong></span> </div>
                                    <div class="label-detailed__single"><strong class="label-detailed__total">$5,550</strong> <span class="label-detailed__status color-danger"><img class="svg" src="{{ asset('cabinet/img/svg/arrow-danger-down.svg') }}" alt=""> <strong>15%</strong></span> </div>
                                 </div>
                                 <div class="parentContainer">


                                    <div>
                                       <canvas id="salesGrowthWeek"></canvas>
                                    </div>


                                 </div>
                              </div>
                              <div class="tab-pane" id="salesgrowth3" role="tabpanel" aria-labelledby="salesgrowth3-tab">
                                 <div class="label-detailed label-detailed--two">
                                    <div class="label-detailed__single"><strong class="label-detailed__total">$8,550</strong> <span class="label-detailed__status color-success"><img class="svg" src="{{ asset('cabinet/img/svg/arrow-success-up.svg') }}" alt=""> <strong>25%</strong></span> </div>
                                    <div class="label-detailed__single"><strong class="label-detailed__total">$5,550</strong> <span class="label-detailed__status color-danger"><img class="svg" src="{{ asset('cabinet/img/svg/arrow-danger-down.svg') }}" alt=""> <strong>15%</strong></span> </div>
                                 </div>
                                 <div class="parentContainer">


                                    <div>
                                       <canvas id="salesGrowthMonth"></canvas>
                                    </div>


                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>

                  </div>
                  <div class="col-xxl-8 mb-25">

                     <div class="card border-0 h-100">
                        <div class="card-header">
                           <h6>Sales by Location</h6>
                           <div class="card-extra">
                              <ul class="card-tab-links nav-tabs nav" role="tablist">
                                 <li>
                                    <a href="#se_region-today" class="active" data-bs-toggle="tab" id="se_region-today-tab" role="tab" aria-selected="true">Today</a>
                                 </li>
                                 <li>
                                    <a href="#se_region-week" data-bs-toggle="tab" id="se_region-week-tab" role="tab" aria-selected="false">Week</a>
                                 </li>
                                 <li>
                                    <a href="#se_region-month" data-bs-toggle="tab" id="se_region-month-tab" role="tab" aria-selected="false">Month</a>
                                 </li>
                                 <li>
                                    <a href="#se_region-year" data-bs-toggle="tab" id="se_region-year-tab" role="tab" aria-selected="false">Year</a>
                                 </li>
                              </ul>
                              <div class="dropdown dropleft">
                                 <a href="#" role="button" id="else" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img src="{{ asset('cabinet/img/svg/more-horizontal.svg') }}" alt="more-horizontal" class="svg">
                                 </a>
                                 <div class="dropdown-menu" aria-labelledby="else">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                 </div>
                              </div>
                           </div>
                        </div>
                        <div class="card-body pt-sm-30 pb-sm-50 pb-30">
                           <div class="tab-content">
                              <div class="tab-pane active show" id="se_region-today" role="tabpanel" aria-labelledby="se_region-today-tab">
                                 <div class="row">
                                    <div class="col-md-6">

                                       <div class="table-responsive table-top-regions">
                                          <table class="table table--default table-borderless">
                                             <thead>
                                                <tr>
                                                   <th>Top region</th>
                                                   <th>order</th>
                                                   <th>Revenue</th>
                                                </tr>
                                             </thead>
                                             <tbody>
                                                <tr>
                                                   <td>United States</td>
                                                   <td>90</td>
                                                   <td>$536</td>
                                                </tr>
                                                <tr>
                                                   <td>Australia</td>
                                                   <td>70</td>
                                                   <td>$573</td>
                                                </tr>
                                                <tr>
                                                   <td>Canada</td>
                                                   <td>30</td>
                                                   <td>$457</td>
                                                </tr>
                                                <tr>
                                                   <td>Japan</td>
                                                   <td>50</td>
                                                   <td>$524</td>
                                                </tr>
                                                <tr>
                                                   <td>India</td>
                                                   <td>20</td>
                                                   <td>$354</td>
                                                </tr>
                                             </tbody>
                                          </table>
                                       </div>

                                    </div>
                                    <div class="col-md-6 d-flex align-items-center justify-content-center">
                                       <div class="regions-svg">
                                          <div id="s_region-map_T"></div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="tab-pane" id="se_region-week" role="tabpanel" aria-labelledby="se_region-week-tab">
                                 <div class="row">
                                    <div class="col-md-6">

                                       <div class="table-responsive table-top-regions">
                                          <table class="table table--default table-borderless">
                                             <thead>
                                                <tr>
                                                   <th>Top region</th>
                                                   <th>order</th>
                                                   <th>Revenue</th>
                                                </tr>
                                             </thead>
                                             <tbody>
                                                <tr>
                                                   <td>United States</td>
                                                   <td>90</td>
                                                   <td>$536</td>
                                                </tr>
                                                <tr>
                                                   <td>Australia</td>
                                                   <td>70</td>
                                                   <td>$573</td>
                                                </tr>
                                                <tr>
                                                   <td>Canada</td>
                                                   <td>30</td>
                                                   <td>$457</td>
                                                </tr>
                                                <tr>
                                                   <td>Japan</td>
                                                   <td>50</td>
                                                   <td>$524</td>
                                                </tr>
                                                <tr>
                                                   <td>India</td>
                                                   <td>20</td>
                                                   <td>$354</td>
                                                </tr>
                                             </tbody>
                                          </table>
                                       </div>

                                    </div>
                                    <div class="col-md-6 d-flex align-items-center justify-content-center">
                                       <div class="regions-svg">
                                          <div id="s_region-map_W"></div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="tab-pane" id="se_region-month" role="tabpanel" aria-labelledby="se_region-month-tab">
                                 <div class="row">
                                    <div class="col-md-6">

                                       <div class="table-responsive table-top-regions">
                                          <table class="table table--default table-borderless">
                                             <thead>
                                                <tr>
                                                   <th>Top region</th>
                                                   <th>order</th>
                                                   <th>Revenue</th>
                                                </tr>
                                             </thead>
                                             <tbody>
                                                <tr>
                                                   <td>United States</td>
                                                   <td>90</td>
                                                   <td>$536</td>
                                                </tr>
                                                <tr>
                                                   <td>Australia</td>
                                                   <td>70</td>
                                                   <td>$573</td>
                                                </tr>
                                                <tr>
                                                   <td>Canada</td>
                                                   <td>30</td>
                                                   <td>$457</td>
                                                </tr>
                                                <tr>
                                                   <td>Japan</td>
                                                   <td>50</td>
                                                   <td>$524</td>
                                                </tr>
                                                <tr>
                                                   <td>India</td>
                                                   <td>20</td>
                                                   <td>$354</td>
                                                </tr>
                                             </tbody>
                                          </table>
                                       </div>

                                    </div>
                                    <div class="col-md-6 d-flex align-items-center justify-content-center">
                                       <div class="regions-svg">
                                          <div id="s_region-map_M"></div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="tab-pane" id="se_region-year" role="tabpanel" aria-labelledby="se_region-year-tab">
                                 <div class="row">
                                    <div class="col-md-6">

                                       <div class="table-responsive table-top-regions">
                                          <table class="table table--default table-borderless">
                                             <thead>
                                                <tr>
                                                   <th>Top region</th>
                                                   <th>order</th>
                                                   <th>Revenue</th>
                                                </tr>
                                             </thead>
                                             <tbody>
                                                <tr>
                                                   <td>United States</td>
                                                   <td>90</td>
                                                   <td>$536</td>
                                                </tr>
                                                <tr>
                                                   <td>Australia</td>
                                                   <td>70</td>
                                                   <td>$573</td>
                                                </tr>
                                                <tr>
                                                   <td>Canada</td>
                                                   <td>30</td>
                                                   <td>$457</td>
                                                </tr>
                                                <tr>
                                                   <td>Japan</td>
                                                   <td>50</td>
                                                   <td>$524</td>
                                                </tr>
                                                <tr>
                                                   <td>India</td>
                                                   <td>20</td>
                                                   <td>$354</td>
                                                </tr>
                                             </tbody>
                                          </table>
                                       </div>

                                    </div>
                                    <div class="col-md-6 d-flex align-items-center justify-content-center">
                                       <div class="regions-svg">
                                          <div id="s_region-map_Y"></div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>

                  </div>
                  <div class="col-xxl-6 col-lg-6 mb-25">

                     <div class="card border-0 px-25">
                        <div class="card-header px-0 border-0">
                           <h6>Top Selling Products</h6>
                           <div class="card-extra">
                              <ul class="card-tab-links nav-tabs nav" role="tablist">
                                 <li>
                                    <a class="active" href="#t_selling-today" data-bs-toggle="tab" id="t_selling-today-tab" role="tab" aria-selected="true">Today</a>
                                 </li>
                                 <li>
                                    <a href="#t_selling-week" data-bs-toggle="tab" id="t_selling-week-tab" role="tab" aria-selected="true">Week</a>
                                 </li>
                                 <li>
                                    <a href="#t_selling-month" data-bs-toggle="tab" id="t_selling-month-tab" role="tab" aria-selected="true">Month</a>
                                 </li>
                              </ul>
                           </div>
                        </div>
                        <div class="card-body p-0">
                           <div class="tab-content">
                              <div class="tab-pane fade active show" id="t_selling-today" role="tabpanel" aria-labelledby="t_selling-today-tab">
                                 <div class="selling-table-wrap">
                                    <div class="table-responsive">
                                       <table class="table table--default table-borderless">
                                          <thead>
                                             <tr>
                                                <th>PRDUCTS NAME</th>
                                                <th>Price</th>
                                                <th>Sold</th>
                                                <th>Revenue</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="radius-xs img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/premium-clock.png') }}" alt="img">
                                                      <span>Premium Clock</span>
                                                   </div>
                                                </td>
                                                <td>$280</td>
                                                <td>339</td>
                                                <td>$38,536</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="radius-xs img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/t-shirt.png') }}" alt="img">
                                                      <span>Boys T-Shirt</span>
                                                   </div>
                                                </td>
                                                <td>$25</td>
                                                <td>80</td>
                                                <td>$20,573</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="radius-xs img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/nike.png') }}" alt="img">
                                                      <span>Nike Shoes</span>
                                                   </div>
                                                </td>
                                                <td>$32</td>
                                                <td>58</td>
                                                <td>$17,457</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="radius-xs img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/cup-glass.png') }}" alt="img">
                                                      <span>Smart Glass</span>
                                                   </div>
                                                </td>
                                                <td>$9,50</td>
                                                <td>36</td>
                                                <td>$15,354</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="radius-xs img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/women-bag.png') }}" alt="img">
                                                      <span>Women Bag</span>
                                                   </div>
                                                </td>
                                                <td>$32</td>
                                                <td>40</td>
                                                <td>$12,354</td>
                                             </tr>
                                          </tbody>
                                       </table>
                                    </div>
                                 </div>
                              </div>
                              <div class="tab-pane fade" id="t_selling-week" role="tabpanel" aria-labelledby="t_selling-week-tab">
                                 <div class="selling-table-wrap">
                                    <div class="table-responsive">
                                       <table class="table table--default table-borderless">
                                          <thead>
                                             <tr>
                                                <th>PRDUCTS NAME</th>
                                                <th>Price</th>
                                                <th>Sold</th>
                                                <th>Revenue</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/287.png') }}" alt="img">
                                                      <span>Samsung Galaxy S8 256GB</span>
                                                   </div>
                                                </td>
                                                <td>$289</td>
                                                <td>339</td>
                                                <td>$60,258</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid" src="{{ asset('cabinet/img/165.png') }}" alt="img">
                                                      <span>Half Sleeve Shirt</span>
                                                   </div>
                                                </td>
                                                <td>$29</td>
                                                <td>136</td>
                                                <td>$2,483</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/166.png') }}" alt="img">
                                                      <span>Marco Shoes</span>
                                                   </div>
                                                </td>
                                                <td>$59</td>
                                                <td>448</td>
                                                <td>$19,758</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/315.png') }}" alt="img">
                                                      <span>15" Mackbook Pro</span>
                                                   </div>
                                                </td>
                                                <td>$1,299</td>
                                                <td>159</td>
                                                <td>$197,458</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/506.png') }}" alt="img">
                                                      <span>Apple iPhone X</span>
                                                   </div>
                                                </td>
                                                <td>$899</td>
                                                <td>146</td>
                                                <td>115,254</td>
                                             </tr>
                                          </tbody>
                                       </table>
                                    </div>
                                 </div>
                              </div>
                              <div class="tab-pane fade" id="t_selling-month" role="tabpanel" aria-labelledby="t_selling-month-tab">
                                 <div class="selling-table-wrap">
                                    <div class="table-responsive">
                                       <table class="table table--default table-borderless">
                                          <thead>
                                             <tr>
                                                <th>PRDUCTS NAME</th>
                                                <th>Price</th>
                                                <th>Sold</th>
                                                <th>Revenue</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/287.png') }}" alt="img">
                                                      <span>Samsung Galaxy S8 256GB</span>
                                                   </div>
                                                </td>
                                                <td>$149</td>
                                                <td>389</td>
                                                <td>$60,278</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid" src="{{ asset('cabinet/img/165.png') }}" alt="img">
                                                      <span>Half Sleeve Shirt</span>
                                                   </div>
                                                </td>
                                                <td>$59</td>
                                                <td>136</td>
                                                <td>$2,483</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/166.png') }}" alt="img">
                                                      <span>Marco Shoes</span>
                                                   </div>
                                                </td>
                                                <td>$89</td>
                                                <td>448</td>
                                                <td>$19,758</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/315.png') }}" alt="img">
                                                      <span>15" Mackbook Pro</span>
                                                   </div>
                                                </td>
                                                <td>$1,29</td>
                                                <td>15</td>
                                                <td>$197,8</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <img class="me-15 wh-34 img-fluid order-bg-opacity-primary" src="{{ asset('cabinet/img/506.png') }}" alt="img">
                                                      <span>Apple iPhone X</span>
                                                   </div>
                                                </td>
                                                <td>$99</td>
                                                <td>16</td>
                                                <td>1154</td>
                                             </tr>
                                          </tbody>
                                       </table>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>

                  </div>
                  <div class="col-xxl-6 col-lg-6 mb-25">

                     <div class="card border-0 px-25 browser-states">
                        <div class="card-header px-0 border-0">
                           <h6>Browser States</h6>
                           <div class="card-extra">
                              <ul class="card-tab-links nav-tabs nav" role="tablist">
                                 <li>
                                    <a class="active" href="#t_selling-today2" data-bs-toggle="tab" id="t_selling-today2-tab" role="tab" aria-selected="true">Today</a>
                                 </li>
                                 <li>
                                    <a href="#t_selling-week2" data-bs-toggle="tab" id="t_selling-week2-tab" role="tab" aria-selected="true">Week</a>
                                 </li>
                                 <li>
                                    <a href="#t_selling-month3" data-bs-toggle="tab" id="t_selling-month3-tab" role="tab" aria-selected="true">Month</a>
                                 </li>
                              </ul>
                           </div>
                        </div>
                        <div class="card-body p-0">
                           <div class="tab-content">
                              <div class="tab-pane fade active show" id="t_selling-today2" role="tabpanel" aria-labelledby="t_selling-today2-tab">
                                 <div class="selling-table-wrap">
                                    <div class="table-responsive">
                                       <table class="table table--default table-borderless">
                                          <thead>
                                             <tr>
                                                <th>Browsers</th>
                                                <th>Session</th>
                                                <th>Bounce Rate</th>
                                                <th>CTE</th>
                                                <th>Goal Conv. Rate</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Google Chrome</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Mozila Firefox</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Apple Safari</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Internet Explorer</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Opera Mini</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                          </tbody>
                                       </table>
                                    </div>
                                 </div>
                              </div>
                              <div class="tab-pane fade" id="t_selling-week2" role="tabpanel" aria-labelledby="t_selling-week2-tab">
                                 <div class="selling-table-wrap">
                                    <div class="table-responsive">
                                       <table class="table table--default table-borderless">
                                          <thead>
                                             <tr>
                                                <th>Customer</th>
                                                <th>Session</th>
                                                <th>Bounce Rate</th>
                                                <th>CTE</th>
                                                <th>Goal Conv. Rate</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Google Chrome</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Mozila Firefox</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Apple Safari</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Internet Explorer</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Opera Mini</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                          </tbody>
                                       </table>
                                    </div>
                                 </div>
                              </div>
                              <div class="tab-pane fade" id="t_selling-month3" role="tabpanel" aria-labelledby="t_selling-month3-tab">
                                 <div class="selling-table-wrap">
                                    <div class="table-responsive">
                                       <table class="table table--default table-borderless">
                                          <thead>
                                             <tr>
                                                <th>Customer</th>
                                                <th>Session</th>
                                                <th>Bounce Rate</th>
                                                <th>CTE</th>
                                                <th>Goal Conv. Rate</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Google Chrome</span>
                                                   </div>
                                                </td>
                                                <td>73,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Mozila Firefox</span>
                                                   </div>
                                                </td>
                                                <td>45,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Apple Safari</span>
                                                   </div>
                                                </td>
                                                <td>57,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Internet Explorer</span>
                                                   </div>
                                                </td>
                                                <td>92,345</td>
                                                <td>3.5%</td>
                                                <td>
                                                   12025
                                                </td>
                                                <td>90%</td>
                                             </tr>
                                             <tr>
                                                <td>
                                                   <div class="selling-product-img d-flex align-items-center">
                                                      <span>Opera Mini</span>
                                                   </div>
                                                </td>
                                                <td>78,345</td>
                                                <td>1.5%</td>
                                                <td>
                                                   48584
                                                </td>
                                                <td>80%</td>
                                             </tr>
                                          </tbody>
                                       </table>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>

                  </div>
               </div>
               <!-- ends: .row -->
            </div>
         </div>
 
      </div>
      <footer class="footer-wrapper">
         <div class="footer-wrapper__inside">
            <div class="container-fluid">
               <div class="row">
                  <div class="col-md-6">
                     <div class="footer-copyright">
                        <p><span>© 2023</span><a href="#">Sovware</a>
                        </p>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="footer-menu text-end">
                        <ul>
                           <li>
                              <a href="#">About</a>
                           </li>
                           <li>
                              <a href="#">Team</a>
                           </li>
                           <li>
                              <a href="#">Contact</a>
                           </li>
                        </ul>
                     </div>
                     <!-- ends: .Footer Menu -->
                  </div>
               </div>
            </div>
         </div>
      </footer>
   </main>
   
   <div id="overlayer">
      <div class="loader-overlay">
         <div class="dm-spin-dots spin-lg">
            <span class="spin-dot badge-dot dot-primary"></span>
            <span class="spin-dot badge-dot dot-primary"></span>
            <span class="spin-dot badge-dot dot-primary"></span>
            <span class="spin-dot badge-dot dot-primary"></span>
         </div>
      </div>
   </div>
   <div class="overlay-dark-sidebar"></div>
   <div class="customizer-overlay"></div>
 <!--  <script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyBgYKHZB_QKKLWfIRaYPCadza3nhTAbv7c"></script>-->
   <!-- inject:js-->

<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery/jquery-3.5.1.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery/jquery-ui.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/bootstrap/popper.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/moment/moment.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/accordion.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/apexcharts.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/autoComplete.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/Chart.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/daterangepicker.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/drawer.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/dynamicBadge.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/dynamicCheckbox.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/footable.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/fullcalendar@5.2.0.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/google-chart.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery-jvectormap-2.0.5.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery-jvectormap-world-mill-en.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.countdown.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.filterizr.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.peity.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.star-rating-svg.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/leaflet.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/leaflet.markercluster.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/loader.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/message.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/moment.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/muuri.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/notification.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/popover.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/select2.full.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/slick.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/trumbowyg.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/wickedpicker.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/apexmain.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/charts.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/drag-drop.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/footable.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/full-calendar.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/googlemap-init.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/icon-loader.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/jvectormap-init.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/leaflet-init.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/main.js') }}"></script>
</body>

</html>