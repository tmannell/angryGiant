{extends file="Head.tpl"}
{block name="navigation"}
  <div id="wrapper">
    <div class="overlay"></div>

    <!-- Sidebar -->
    <nav class="navbar navbar-inverse navbar-fixed-top" id="sidebar-wrapper" role="navigation">
      <ul class="nav sidebar-nav">
        <li class="sidebar-brand">
          {$siteName}
        </li>
        <li>
          <a href="/"><i class="fa fa-fw fa-home"></i> Home</a>
        </li>
        <li>
          <a href="/stories"><i class="fa fa-fw fa-book"></i> Stories</a>
        </li>
        {if $access == 'admin' || $access == 'authorized'}
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-fw fa-cog"></i> Admin <span class="caret"></span></a>
            <ul class="dropdown-menu" role="menu">
              <li class="dropdown-header">Tasks</li>
              <li><a href="/story/add">Add Story</a></li>
              <li><a href="/page/add">Add Page</a></li>
              {if $access == 'admin'}
                <li><a href="/user/add">Add User</a></li>
              {/if}
            </ul>
          </li>
        {/if}
        <li>
          {if $access == 'anonymous'}
            <a href="/user"><i class="fa fa-fw fa-sign-in"></i> Login</a>
          {else}
            <a href="/logout"><i class="fa fa-fw fa-sign-out"></i> Logout</a>
          {/if}
        </li>
      </ul>
    </nav>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div id="page-content-wrapper">
      <button type="button" class="hamburger is-closed animated fadeInLeft" data-toggle="offcanvas">
        <span class="hamb-top"></span>
        <span class="hamb-middle"></span>
        <span class="hamb-bottom"></span>
      </button>
{/block}