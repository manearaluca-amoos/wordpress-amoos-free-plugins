import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

registerBlockType('ams/countdown', {
    title: __('AMS Countdown', 'ams'),
    icon: 'clock',
    category: 'widgets',
    edit() {
        return (
            <div style={{ textAlign: 'center', padding: '20px' }}>
                <strong>{__('Countdown-ul va fi vizibil pe frontend pe baza datei salvate.', 'ams')}</strong>
            </div>
        );
    },
    save() {
        return null; // Va fi randat de PHP (render_callback)
    }
});
