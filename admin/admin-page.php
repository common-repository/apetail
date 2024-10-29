<?php
 if (!current_user_can('manage_options')) die;
 if($_GET['tab']=='settings') $tab='settings';
 elseif($_GET['tab']=='info') $tab='info';
 else $tab=null;
?>
<div class="wrap">
  <h1 class="title"><? esc_html_e('ApeTail admin page','apetail')?></h1>
  <?php settings_errors(); ?>

  <nav class="nav-tab-wrapper">
    <a href="?page=apetail_settings&tab=settings" class="nav-tab <?php if($tab==null||$tab=='settings'):?>nav-tab-active<?php endif; ?>">Settings</a>
    <a href="?page=apetail_settings&tab=info" class="nav-tab <?php if($tab=='info'):?>nav-tab-active<?php endif; ?>">Info</a>
  </nav>

    <div class="tab-content">
    <?php switch($tab) :
      case 'info':
?>
    <p>This plugin is free for the first month and for 3 or less participants, paid application conditions described on ApeTail website. With all questions, please contact the developer using the command <b>/direct @AyuHo</b> in chat interface. If you want to use ChatGPT, you need to increase host USD balance - contact the developer and follow instructions. If you need translation for your language - become paid client and receive it in 3 days. If you have suggestions and development ideas, please share and receive a chance be rewarded with equity tokens of ApeTail. If you have a bug report, please let the developer know as soon as possible.</p>
    <a class='big' target='_blank' href='https://apetail.chat'>ApeTail web site</a>  
<?php

        break;
      default:
?>
      <form method="post" action="options.php">
          <?php
              settings_fields('apetail_settings');
              do_settings_sections('apetail_settings');
              submit_button();
          ?>    
      </form>
<?php
        break;
    endswitch; ?>
    </div>
</div>