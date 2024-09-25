<div
    style="
        max-width: 1200px;
        margin: 0 auto;
        padding: 16px;
        background-color: #FFF4FF;
        border: 1px solid #81006F;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    "
>
    <h1
        style="
            font-family: 'Onest', sans-serif;
            font-size: 18px;
            font-weight: 800;
            background: linear-gradient(to right, #FC86E2, #4B003F);
            -webkit-background-clip: text;
            color: transparent;
            text-align: center;
            margin: 0;
        "
    >
        First 8 Marketing CRM Analytics
    </h1>
    <p
        style="
            font-family: 'Onest', sans-serif;
            font-size: 18px;
            text-align: center;
            color: #4B003F;
            margin: 0;
        "
    >
        Turn your WordPress into lead-driver, sale-mover & churn-killer. Motivate your high paying customers until they buy.
    </p>
    <div style="display: flex; justify-content: center;">
        <?php if ( empty( $api_key ) ) : ?>
        <button
            style="
                background-color: #81006F;
                color: white;
                font-weight: bold;
                padding: 8px 16px;
                border-radius: 8px;
                border: none;
                cursor: pointer;
                transition: background-color 0.3s ease;
            "
            onmouseover="this.style.backgroundColor='#4B003F'"
            onmouseout="this.style.backgroundColor='#81006F'"
            class="btn_action"
            data-action="first_8_marketing_connect"
        >
            <?php esc_html_e( 'Connect' ); ?>
        </button>
        <?php else : ?>
        <div style="display: flex; justify-content: center; gap: 12px;">
            <button
                style="
                    background-color: #B91C1C;
                    color: white;
                    font-weight: bold;
                    padding: 8px 16px;
                    border-radius: 8px;
                    border: none;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                "
                class="btn_action"
                data-action="first_8_marketing_disconnect"
            >
                <?php esc_html_e( 'Disconnect' ); ?>
            </button>
            <?php if ( $is_contents_have_been_exported === '0' ) : ?>
            <button
                id="export-button"
                style="
                    background-color: #81006F;
                    color: white;
                    font-weight: bold;
                    padding: 8px 16px;
                    border-radius: 8px;
                    border: none;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                "
                onmouseover="this.style.backgroundColor='#4B003F'"
                onmouseout="this.style.backgroundColor='#81006F'"
                data-action="first_8_marketing_export_post"
            >
                <?php esc_html_e( 'Export Post to ICP' ); ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <script>
            jQuery(document).ready(function($) {
                $('.btn_action').click(function() {
                    var action = $(this).data('action');
                    $.ajax({
                        url: '<?php echo esc_url(admin_url( 'admin-ajax.php' )); ?>',
                        type: 'POST',
                        data: {
                            action: action,
                        },
                        success: function(response) {
                            console.log(response);
                            if (response.success) {
                                switch (action) {
                                    case 'first_8_marketing_connect':
                                        if (response?.data?.redirect_url) {
                                            window.location.href = response?.data?.redirect_url;
                                        }
                                        break;
                                    case 'first_8_marketing_disconnect':
                                        let currentUrl = new URL(window.location.href);
                                        let searchParams = currentUrl.searchParams;
                                        searchParams.delete('api_key');
                                        window.location.href = currentUrl.toString();
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    });
                });
            });
            jQuery(document).ready(function($) {
                $('#export-button').on('click', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    button.prop('disabled', true).text('Loading...');

                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'POST',
                        data: {
                            action: 'first_8_marketing_export_post',
                        },
                        success: function(response) {
                            if (response.success) {
                                button.remove();
                                alert('Posts exported and sent successfully!');
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                            button.prop('disabled', false).text('Export Post to ICP');
                        },
                        error: function(xhr, status, error) {
                            alert('An error occurred: ' + error);
                            button.prop('disabled', false).text('Export Post to ICP');
                        }
                    });
                });
            });
        </script>
    </div>
</div>
