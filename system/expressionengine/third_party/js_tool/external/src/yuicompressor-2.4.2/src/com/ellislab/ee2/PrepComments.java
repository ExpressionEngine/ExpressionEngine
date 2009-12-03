/**
 * A quick explanation as to what is going on here. Ideally we could simply tap into
 * the existing InputStream that the YUI Compressor creates, grab the first 3 chars
 * and if they're not a comment (or match /*! ) - we'd rewind it.
 * Unfortunately it doesn't work that way, because the InputStreamReader cannot be reset.  That isn't
 * a problem for files that have either comment style (we could just remove the '!').  But
 * for files that don't start with a commment, we need to be able to reset.
 *
 * So instead, we open a separate stream to check the first comment, and possibly grab it for
 * safekeeping. Then we simply drop it into the output stream right after that is created.
 *
 * Problem solved. Stupid solution.
 */
package com.ellislab.ee2;

import java.io.*;
import java.nio.charset.Charset;

public class PrepComments
{
	private String firstComment;
	
	public PrepComments()
	{
		this.firstComment = "";
	}
	
	/**
	 * Get File Handle
	 *
	 * Creates a new file stream, checks comment, and returns a new (different)
	 * stream for YUI to use.
	 *
	 * @param	InputStream
	 * @param	charset
	 */
	public Reader get_ee2_file_handle(InputStream filein, String charset)
	{		
		try {
			this.handleFile(new InputStreamReader(filein, charset));
			return new InputStreamReader(filein, charset);
		}
		catch (IOException e) {
            e.printStackTrace();
            System.exit(1);
        }

		return null;
	}
	
	/**
	 * Get File Handle
	 *
	 * Overloaded to take filenames where appropriate
	 *
	 * @param	Filename
	 */
	public Reader get_ee2_file_handle(String filein, String charset)
	{
		try {
			this.handleFile(new InputStreamReader(new FileInputStream(filein), charset));
			return new InputStreamReader(new FileInputStream(filein), charset);
		}
		catch (IOException e) {
            e.printStackTrace();
            System.exit(1);
        }

		return null;
	}
	
	/**
	 * HandleFile
	 *
	 * Takes the input stream and checks the first three characters
	 * for a valid comment.  If a regular comment is found it parses
	 * the entire thing out and stores it in lastComment
	 *
	 * @param	InputStream to check
	 */
	protected void handleFile(InputStreamReader input)
	{
		try {
			Reader commentBuffer = new BufferedReader(input, 3);

			// Read the first 3 chars
			char[] firstThree = new char[3];
			commentBuffer.read(firstThree, 0, 3);

			String comment = new String(firstThree);

			// Is it a valid comment?
			
			if (!comment.equals("/*!")) {
				if (comment.substring(0, 2).equals("/*")) {

					this.firstComment = comment;

					int nextChar = commentBuffer.read();
					boolean mark = false;

					Character asteriks = new Character('*');
					Character slash = new Character('/');

					// Grab the whole thing up to the closing */
					while (nextChar != -1) {
						Character thechar = new Character((char) nextChar);

						if (thechar.equals(slash) && mark == true) {
							this.firstComment += thechar.toString();
							break;
						}
						mark = false;

						if (thechar.equals(asteriks)) {
							mark = true;
						}
						this.firstComment += thechar.toString();
						nextChar = commentBuffer.read();
					}
					
					firstComment += "\n";
				}
				else {
					// Nothing we can do here
					this.firstComment = "";
				}
			}
			else {
				this.firstComment = "";
			}
			
			commentBuffer.close();
		}
		catch (IOException e) {
            e.printStackTrace();
            System.exit(1);
        }
		
	}
	
	/**
	 * getComment
	 *
	 * Returns the comment (added to the output stream)
	 *
	 * @return	string
	 */
	public String getComment()
	{
		return this.firstComment;
	}
}