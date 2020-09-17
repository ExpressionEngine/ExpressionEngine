import FileManagerSection from '../_sections/FileManagerSection'

class UploadSync extends FileManagerSection {
  constructor() {
    super();

    this.elements({
      'progress_bar': '.progress-bar .progress',
      'sync_button': 'div.form-standard form div.form-btns-top input.btn',
      'sizes': 'input[name="sizes[]"]'
    });
  }

  // Load the sync screen for the nth directory listed
  load_sync_for_dir(number) {
    cy.contains('Files').click()

    cy.get('div.sidebar .folder-list > li:nth-child('+number.toString()+') > a').click()
    cy.get('a.icon--sync').click()
  }

  // Each time the progress bar changes, logs the percentage value
  // so we can make sure it is progressing properly
  /*log_progress_bar_moves() {
    i = 0;
    sizes = [0];

    while self.progress_bar['style'] != 'width: 100%; ' && i < 500

      // Get the raw number out of the style
      width = self.progress_bar['style'][/\d+/].to_i

      if ( ! sizes.include? width) then
        sizes.push width
      }

      i += 1 // Prevent infinite loop
    }

    return sizes.push(100).uniq
  }*/

  // Do the same calculations the syncronization JS is doing to
  // determine what the proper percentages we should be seeing are;
  // we'll compare these to what we actually got
  /*progress_bar_moves_for_file_count(count) {
    processed = 0
    sizes = [0];

    while processed + 5 < count
      processed += 5
      progress = (processed.to_f / count.to_f) * 100.0
      sizes.push progress.round
    }

    sizes.push 100
    return sizes
  }*/
}
export default UploadSync;
