import ControlPanel from '../ControlPanel'

class ChannelManager extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/channels';

        this.elements({
            "channels": '.list-group > li a.list-item__content',
            "channels_checkboxes": '.list-group > li input[type="checkbox"]',
            "select_all": '.ctrl-all input',
            "import": 'a[rel=import-channel]',
            "delete": 'input[value="Confirm and Delete"]'
        })
    }

    // Get a channel ID from a channel name or title
    //
    // @param[String] name The channel name / title to look for
    // @raise[RuntimeError] if the channel name does not exist
    // @return [Integer] The channel's ID
    get_channel_id_from_name(name) {
        return cy.task('db:query', 'SELECT channel_id FROM exp_channels WHERE channel_name = "' + name + '"').then(function([rows, fields]) {
                return rows[0];
            })
            // raise 'No known channel'
    }

    getChannelTitles() {
        return cy.task('db:query', 'SELECT channel_title FROM exp_channels ORDER BY channel_title ASC').then(function([rows, fields]) {
            return rows.map(function(row) {
                return row.channel_title;
            });
        })
    }

}
export default ChannelManager;