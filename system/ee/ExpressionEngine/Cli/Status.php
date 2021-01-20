<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */

namespace ExpressionEngine\Cli;

/**
 *
 * Constants for preferred exit status codes; cf.
 * <http://www.unix.com/man-page/freebsd/3/sysexits/>.
 *
 * @package Aura.Cli
 *
 */
class Status
{
    /**
     * Success
     */
    const SUCCESS = 0;

    /**
     * Failure
     */
    const FAILURE = 1;

    /**
     * The command was used incorrectly, e.g., with the wrong number of
     * arguments, a bad flag, a bad syntax in a parameter, or whatever.
     */
    const USAGE = 64;

    /**
     * The input data was incorrect in some way. This should only be used for
     * user’s data and not system files.
     */
    const DATAERR = 65;

    /**
     * An input file (not a system file) did not exist or was not readable.
     * This could also include errors like "No message" to a mailer (if it
     * cared to catch it).
     */
    const NOINPUT = 66;

    /**
     * The user specified did not exist. This might be used for mail addresses
     * or remote logins.
     */
    const NOUSER = 67;

    /**
     * The host specified did not exist. This is used in mail addresses or
     * network requests.
     */
    const NOHOST = 68;

    /**
     * A service is unavailable. This can occur if a support program or file
     * does not exist. This can also be used as a catchall message when
     * something you wanted to do does not work, but you do not know why.
     */
    const UNAVAILABLE = 69;

    /**
     * An internal software error has been detected. This should be limited
     * to non-operating system related errors as possible.
     */
    const SOFTWARE = 70;

    /**
     * An operating system error has been detected. This is intended to be
     * used for such things as "cannot fork", "cannot create pipe", or the
     * like. It includes things like getuid returning a user that does not
     * exist in the passwd file.
     */
    const OSERR = 71;

    /**
     * Some system file (e.g., /etc/passwd, /var/run/utmp, etc.) does not
     * exist, cannot be opened, or has some sort of error (e.g., syntax
     * error).
     */
    const OSFILE = 72;

    /**
     * A (user specified) output file cannot be created.
     */
    const CANTCREAT = 73;

    /**
     * An error occurred while doing I/O on some file.
     */
    const IOERR = 74;

    /**
     * Temporary failure, indicating something that is not really an error.
     * In sendmail, this means that a mailer (e.g.) could not create a
     * connection, and the request should be reattempted later.
     */
    const TEMPFAIL = 75;

    /**
     * The remote system returned something that was "not possible" during a
     * protocol exchange.
     */
    const PROTOCOL = 76;

    /**
     * You did not have sufficient permission to perform the operation. This
     * is not intended for file system problems, which should use NOINPUT
     * or CANTCREAT, but rather for higher level permissions.
     */
    const NOPERM = 77;

    /**
     * Something was found in an unconfigured or misconfigured state.
     */
    const CONFIG = 78;
}
