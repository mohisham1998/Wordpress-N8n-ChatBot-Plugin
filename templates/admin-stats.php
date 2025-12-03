<?php
/**
 * Admin Stats Page Template
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap automize-chat-admin automize-stats-page" dir="rtl">
    <h1>
        <span class="dashicons dashicons-chart-bar"></span>
        إحصائيات المحادثات
    </h1>
    
    <div class="automize-stats-grid">
        <!-- Main Stats -->
        <div class="stats-section stats-main">
            <h2>نظرة عامة</h2>
            <div class="stats-cards-grid">
                <div class="stat-card large">
                    <div class="stat-icon">💬</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['total_sessions'] ) ); ?></span>
                        <span class="stat-label">إجمالي المحادثات</span>
                    </div>
                </div>
                <div class="stat-card large">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['total_messages'] ) ); ?></span>
                        <span class="stat-label">إجمالي الرسائل</span>
                    </div>
                </div>
                <div class="stat-card large highlight">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['leads'] ) ); ?></span>
                        <span class="stat-label">عملاء محتملين</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Time Period Stats -->
        <div class="stats-section">
            <h2>الفترة الزمنية</h2>
            <div class="stats-cards-grid">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['today_sessions'] ) ); ?></span>
                        <span class="stat-label">محادثات اليوم</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📆</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['this_week_sessions'] ) ); ?></span>
                        <span class="stat-label">هذا الأسبوع</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Stats -->
        <div class="stats-section">
            <h2>حسب الحالة</h2>
            <div class="stats-cards-grid">
                <div class="stat-card status-active">
                    <div class="stat-icon">🟢</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['active_sessions'] ) ); ?></span>
                        <span class="stat-label">نشط</span>
                    </div>
                </div>
                <div class="stat-card status-completed">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['completed_sessions'] ) ); ?></span>
                        <span class="stat-label">مكتمل</span>
                    </div>
                </div>
                <div class="stat-card status-abandoned">
                    <div class="stat-icon">⚪</div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html( number_format_i18n( $stats['abandoned_sessions'] ) ); ?></span>
                        <span class="stat-label">متروك</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
