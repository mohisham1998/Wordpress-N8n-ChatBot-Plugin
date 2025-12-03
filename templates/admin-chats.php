<?php
/**
 * Admin Chats Page Template
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap automize-chat-admin" dir="rtl">
    <!-- Toast Container -->
    <div id="automize-toast-container" class="automize-toast-container"></div>
    
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-format-chat"></span>
        محادثات أوتومايز
        <span class="live-indicator">مباشر</span>
    </h1>
    
    <!-- Stats Cards -->
    <div class="automize-stats-cards">
        <div class="stat-card stat-total">
            <div class="stat-icon">💬</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['total_sessions'] ) ); ?></span>
                <span class="stat-label">إجمالي المحادثات</span>
            </div>
        </div>
        <div class="stat-card stat-today">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['today_sessions'] ) ); ?></span>
                <span class="stat-label">محادثات اليوم</span>
            </div>
        </div>
        <div class="stat-card stat-leads">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['leads'] ) ); ?></span>
                <span class="stat-label">عملاء محتملين</span>
            </div>
        </div>
        <div class="stat-card stat-active">
            <div class="stat-icon">🟢</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['active_sessions'] ) ); ?></span>
                <span class="stat-label">محادثات نشطة</span>
            </div>
        </div>
    </div>
    
    <!-- Filters Bar -->
    <div class="automize-filters-bar">
        <form method="get" class="automize-filter-form">
            <input type="hidden" name="page" value="automize-chats">
            
            <div class="filter-group">
                <label for="status-filter">الحالة:</label>
                <select name="status" id="status-filter">
                    <option value="">الكل</option>
                    <option value="active" <?php selected( $status_filter, 'active' ); ?>>نشط</option>
                    <option value="completed" <?php selected( $status_filter, 'completed' ); ?>>مكتمل</option>
                    <option value="lead" <?php selected( $status_filter, 'lead' ); ?>>عميل محتمل</option>
                    <option value="abandoned" <?php selected( $status_filter, 'abandoned' ); ?>>متروك</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date-from">من تاريخ:</label>
                <input type="date" name="date_from" id="date-from" value="<?php echo esc_attr( $date_from ); ?>">
            </div>
            
            <div class="filter-group">
                <label for="date-to">إلى تاريخ:</label>
                <input type="date" name="date_to" id="date-to" value="<?php echo esc_attr( $date_to ); ?>">
            </div>
            
            <div class="filter-group search-group">
                <label for="search-input">بحث:</label>
                <input type="search" name="s" id="search-input" value="<?php echo esc_attr( $search ); ?>" placeholder="ابحث بالاسم، البريد، الهاتف...">
            </div>
            
            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-search"></span>
                تصفية
            </button>
            
            <?php if ( $status_filter || $search || $date_from || $date_to ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=automize-chats' ) ); ?>" class="button">
                    مسح الفلاتر
                </a>
            <?php endif; ?>
        </form>
        
        <div class="bulk-actions">
            <button type="button" class="button" id="export-btn">
                <span class="dashicons dashicons-download"></span>
                تصدير CSV
            </button>
            <button type="button" class="button button-link-delete" id="delete-selected-btn">
                <span class="dashicons dashicons-trash"></span>
                حذف المحدد
            </button>
        </div>
    </div>
    
    <!-- Chats Table -->
    <div class="automize-chats-table-wrapper">
        <table class="wp-list-table widefat fixed striped automize-chats-table">
            <thead>
                <tr>
                    <td class="check-column">
                        <input type="checkbox" id="select-all-chats">
                    </td>
                    <th class="column-location">الموقع</th>
                    <th class="column-messages">الرسائل</th>
                    <th class="column-status">الحالة</th>
                    <th class="column-date">التاريخ</th>
                    <th class="column-actions">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $sessions ) ) : ?>
                    <tr>
                        <td colspan="6" class="no-items">
                            <div class="empty-state">
                                <span class="dashicons dashicons-format-chat"></span>
                                <p>لا توجد محادثات حتى الآن</p>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $sessions as $session ) : ?>
                        <?php
                        $last_message = $message_repo->get_last( $session->session_id );
                        $status_class = 'status-' . $session->status;
                        $status_label = AutoMize_Chat_Session_Repository::get_status_label( $session->status );
                        $country_flag = AutoMize_Chat_Session_Repository::get_country_flag( isset( $session->visitor_country_code ) ? $session->visitor_country_code : '' );
                        $city_display = ! empty( $session->visitor_city ) ? $session->visitor_city : '';
                        $has_contact  = ! empty( $session->visitor_name ) || ! empty( $session->visitor_email ) || ! empty( $session->visitor_phone );
                        $row_class    = $has_contact ? 'has-contact-info' : '';
                        ?>
                        <tr data-session-id="<?php echo esc_attr( $session->session_id ); ?>" class="<?php echo esc_attr( $row_class ); ?>">
                            <th class="check-column">
                                <input type="checkbox" class="chat-checkbox" value="<?php echo esc_attr( $session->session_id ); ?>">
                            </th>
                            <td class="column-location">
                                <?php if ( ! empty( $city_display ) || ! empty( $session->visitor_country_code ) ) : ?>
                                    <div class="location-wrapper">
                                        <span class="location-flag" title="<?php echo esc_attr( isset( $session->visitor_country ) ? $session->visitor_country : '' ); ?>">
                                            <?php echo $country_flag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        </span>
                                        <span class="location-city"><?php echo esc_html( $city_display ? $city_display : $session->visitor_country ); ?></span>
                                    </div>
                                <?php else : ?>
                                    <span class="location-unknown">غير معروف</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-messages">
                                <span class="message-count"><?php echo esc_html( number_format_i18n( $session->messages_count ) ); ?></span>
                                <?php if ( $last_message ) : ?>
                                    <div class="last-message" title="<?php echo esc_attr( $last_message->message ); ?>">
                                        <?php echo esc_html( mb_substr( $last_message->message, 0, 50 ) ); ?>
                                        <?php echo mb_strlen( $last_message->message ) > 50 ? '...' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <span class="status-badge <?php echo esc_attr( $status_class ); ?>">
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </td>
                            <td class="column-date">
                                <div class="date-info">
                                    <span class="date-value"><?php echo esc_html( AutoMize_Chat_Admin_Page_Chats::format_date_only( $session->started_at ) ); ?></span>
                                    <span class="time-value"><?php echo esc_html( AutoMize_Chat_Admin_Page_Chats::format_time_only( $session->started_at ) ); ?></span>
                                    <?php if ( $session->last_message_at && $session->last_message_at !== $session->started_at ) : ?>
                                        <small>آخر رسالة: <?php echo esc_html( AutoMize_Chat_Admin_Page_Chats::time_ago( $session->last_message_at ) ); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button view-chat-btn" data-session-id="<?php echo esc_attr( $session->session_id ); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    عرض
                                </button>
                                <button type="button" class="button button-link-delete delete-chat-btn" data-session-id="<?php echo esc_attr( $session->session_id ); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ( $total_pages > 1 ) : ?>
        <div class="automize-pagination">
            <?php
            $base_url = add_query_arg(
                array(
                    'page'      => 'automize-chats',
                    'status'    => $status_filter,
                    's'         => $search,
                    'date_from' => $date_from,
                    'date_to'   => $date_to,
                ),
                admin_url( 'admin.php' )
            );

            echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                array(
                    'base'      => $base_url . '%_%',
                    'format'    => '&paged=%#%',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'prev_text' => '&rarr; السابق',
                    'next_text' => 'التالي &larr;',
                    'type'      => 'list',
                )
            );
            ?>
            <span class="pagination-info">
                عرض <?php echo esc_html( ( ( $current_page - 1 ) * $per_page ) + 1 ); ?> - 
                <?php echo esc_html( min( $current_page * $per_page, $total_items ) ); ?> 
                من <?php echo esc_html( number_format_i18n( $total_items ) ); ?>
            </span>
        </div>
    <?php endif; ?>
</div>

<!-- Chat Modal -->
<div id="chat-modal" class="automize-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h2>
                <span class="dashicons dashicons-format-chat"></span>
                <span id="modal-title">عرض المحادثة</span>
            </h2>
            <button type="button" class="modal-close" aria-label="إغلاق">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="modal-body">
            <div class="chat-info-bar" id="chat-info-bar">
                <!-- Visitor info will be added here -->
            </div>
            <div class="chat-messages-container" id="chat-messages-container">
                <!-- Messages will be added here -->
            </div>
        </div>
        <div class="modal-footer">
            <select id="change-status-select" class="status-select">
                <option value="">تغيير الحالة...</option>
                <option value="active">🟢 نشط</option>
                <option value="completed">🔵 مكتمل</option>
                <option value="lead">🟡 عميل محتمل</option>
                <option value="abandoned">⚫ متروك</option>
            </select>
            <button type="button" class="button" id="close-modal-btn">إغلاق</button>
        </div>
    </div>
</div>
