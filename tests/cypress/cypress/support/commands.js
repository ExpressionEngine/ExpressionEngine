// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//

import 'cypress-maildev';
import 'cypress-audit/commands';
import 'cypress-real-events';

//https://github.com/cypress-io/cypress/issues/249
const COMMAND_DELAY = Cypress.env('COMMAND_DELAY') || 0;
if (COMMAND_DELAY > 0) {
    for (const command of ['visit', 'click', 'trigger', 'type', 'clear', 'reload', 'contains']) {
        Cypress.Commands.overwrite(command, (originalFn, ...args) => {
            const origVal = originalFn(...args);

            return new Promise((resolve) => {
                setTimeout(() => {
                    resolve(origVal);
                }, COMMAND_DELAY);
            });
        });
    }
}

// -- This is a parent command --
Cypress.Commands.add("login", (user) => {
    if (Cypress.$('input[name=username]:visible').length == 0) {
        return;
    }

    if (!user) {
        user = {
            email: Cypress.env("USER_EMAIL"),
            password: Cypress.env("USER_PASSWORD"),
        }
    }

    if (user.email) {
        cy.get('input[name=username]').clear().type(user.email)
    }

    if (user.password) {
        cy.get('input[name=password]').clear().type(user.password)
    }

    cy.get('input[type=submit]').click()
})

Cypress.Commands.add("logout", () => {
    cy.visit('admin.php?/cp/members/profile/settings')
    cy.dismissLicenseAlert()
    cy.get('.main-nav__account-icon > img').click()
    cy.get('[href="admin.php?/cp/login/logout"]').click()
})

Cypress.Commands.add("auth", (user) => {
    cy.visit('admin.php', {failOnStatusCode: false});
    cy.login(user);
})

Cypress.Commands.add("addMembers", (group, count, password = 'password') => {
    if (password == 'password') {
        cy.eeConfig({ item: 'password_security_policy', value: 'none' })
    }
    cy.auth();
    let i = 1;
    for(i ; i <= count; i++){
        cy.visit('/admin.php?/cp/members/create') //goes to member creation url
        cy.dismissLicenseAlert()

        let email = group;
        email += i.toString();
        email += "@test.com";
        let username = group + i.toString();
        cy.get('input[name=username]:visible').clear().type(username)
        cy.get('input[name=email]:visible').clear().type(email)
        cy.get('input[name=password]:visible').clear().type(password)
        cy.get('input[name=confirm_password]:visible').clear().type(password)

        cy.get("body").then($body => {
            if ($body.find("input[name=verify_password]:visible").length > 0) {   //evaluates as true if verify is needed
                cy.get("input[name=verify_password]").type('password');
            }
        });
        cy.get('button').contains('Roles').click()
        cy.get('label').contains(group).click()
        cy.get('.form-btns-top .saving-options').click()
        cy.get('form .form-btns-top button[type=submit][value=save_and_new]').click()
    }
})

Cypress.Commands.add("addRole", (role) => {
    cy.auth();
    cy.visit('admin.php?/cp/members/roles')
    cy.dismissLicenseAlert()
    cy.get('a').contains('New Role').click()
    cy.get('input[name="name"]').clear().type(role)
    cy.get('.form-btns-top .saving-options').click()
    cy.get('button').contains('Save & Close').eq(0).click()
})

Cypress.Commands.add("authVisit", (url, user) => {
    cy.auth(user);
    cy.visit(url);
})

Cypress.Commands.add("logFrontendPerformance", () => {
    cy.get('#elapsed_time em').invoke('text').then((elapsed_time) => {
        cy.log("Elapsed time: " + elapsed_time)
        cy.get('#memory_usage em').invoke('text').then((memory_usage) => {
            cy.log("Memory usage: " + memory_usage)
            var testInfo = Cypress.currentTest.titlePath.reverse()
            var logEntry = '"' + testInfo[1] + '","' + testInfo[0] + '","' + elapsed_time + '","' + memory_usage + '"' + "\r\n"
            cy.writeFile('cypress/downloads/performance_fe.csv', logEntry, { flag: 'a+' })
        })
    })
})

Cypress.Commands.add("logCPPerformance", () => {
    cy.get('li b:contains("Total Execution Time:")').parent().invoke('text').then((elapsed_time) => {
        elapsed_time = elapsed_time.replace("Total Execution Time:", "").trim();
        cy.log("Elapsed time: " + elapsed_time)
        cy.get('li b:contains("Memory Usage:")').parent().invoke('text').then((memory_usage) => {
            memory_usage = memory_usage.replace("Memory Usage:", "").trim();
            memory_usage = memory_usage.substring(0, memory_usage.indexOf(' '));
            cy.log("Memory usage: " + memory_usage)
            var testInfo = Cypress.currentTest.titlePath.reverse()
            var logEntry = '"' + testInfo[1] + '","' + testInfo[0] + '","' + elapsed_time + '","' + memory_usage + '"' + "\r\n"
            cy.writeFile('cypress/downloads/performance_cp.csv', logEntry, { flag: 'a+' })
        })
    })
})


Cypress.Commands.add("hasNoErrors", () => {
    // Search for "on line" or "Line Number:" since they're in pretty much in every PHP error
    if (cy.url().should('not.include', 'logs/developer')) {
        cy.contains('on line').should('not.exist')
    }

    cy.contains('Line Number:').should('not.exist')
    cy.contains('Severity:').should('not.exist')

    //cy.contains('Errors').should('not.exist')

    // Our custom PHP error handler
    cy.contains(', line').should('not.exist')
    cy.contains('Stack Trace').should('not.exist')

    cy.contains('Exception Caught').should('not.exist')
    cy.contains('Warning Caught').should('not.exist')
    cy.contains('Error Caught').should('not.exist')
})

Cypress.Commands.add("dismissLicenseAlert", () => {
    cy.get('body').then(($body) => {
        if ($body.find('.app-notice-pro-license-error .app-notice__dismiss').length) {
            cy.get('.app-notice-pro-license-error .app-notice__dismiss').trigger('click');
        }
    })
})

Cypress.Commands.add("dragTo", { prevSubject: true }, (subject, target) => {
    cy.wrap(subject).trigger("mousedown", { which: 1 })

    if (typeof target === 'string' || target instanceof String) {
        target = cy.get(target)
    } else {
        target = cy.wrap(target);
    }
    // console.log({ subject, target })
    target.trigger("mousemove", { force: true }).trigger("mouseup", { force: true })

    return target;
})

Cypress.Commands.add("installTheme", (theme, toUser = false) => {
    let themes = '../../themes/'
    let system = '../../system/'

    if (toUser) {
        cy.task('filesystem:copy', {
            from: `${system}ee/templates/_themes/${theme}`,
            to: `${themes}user/`
        })

        cy.task('filesystem:copy', {
            from: `${themes}ee/${theme}`,
            to: `${themes}user/`
        })
    } else {
        cy.task('filesystem:create', `${system}user/templates/_themes`)

        cy.task('filesystem:copy', {
            from: `${system}ee/templates/_themes/${theme}`,
            to: `${system}user/templates/_themes/`
        })

        cy.task('filesystem:copy', {
            from: `${themes}ee/${theme}`,
            to: `${themes}user/`
        })
    }
})

Cypress.Commands.add("uninstallTheme", (theme) => {
    let themes = '../../themes/'
    let system = '../../system/'

    cy.task('filesystem:delete', `${themes}user/${theme}`)
    cy.task('filesystem:delete', `${system}user/templates/_themes/${theme}`)
})

Cypress.Commands.add("eeConfig", ({ item, value, site_id }) => {
    if (!item) {
        return;
    }

    if (value === '') {
        value = 'EMPTY';
    }

    let command = [
        `cd support/fixtures && php config.php ${item}`,
        (value) ? ` ${value}` : '',
        (site_id) ? ` --site-id ${site_id}` : ''
    ].join('');

    //cy.exec('ddev exec --dir /var/www/html/tests/cypress "' + command + '"').then((harvest) => {
    console.log(command)
    cy.exec(command).then((harvest) => {
        return harvest.stdout;
    })
})

// Create a number of entries
//
// @param [Number] n = 10 Set a specific number of entries to create, defaults
//   to 10
// @return [void]
Cypress.Commands.add("createEntries", ({ n, channel }) => {

    if (!n) n = 10
    if (!channel) channel = 1

    let command = [
        `cd support/fixtures && php entries.php`,
        `--number ${n}`,
        `--channel ${channel}`
    ].join(' ')

    cy.exec(command)
})

Cypress.Commands.add("createChannel", ({ max_entries }) => {
    let command = `cd support/fixtures && php channels.php`;

    // include opts, change _ in hash symbols to - to standardize CLI behavior
    if (max_entries) {
        command += ` --max-entries ${max_entries}`
    }

    cy.exec(command).then((harvest) => {

        return harvest.stdout;
    })

})

Cypress.Commands.add("createMembers", ({ n }) => {

    if (!n) n = 1

    let command = [
        `cd support/fixtures && php member.php`,
        `--number ${n} --batch`
    ].join(' ')

    cy.exec(command)
})

// Alternative approach for overcoming CKEditor bug with Cypress .type() command
// https://github.com/ckeditor/ckeditor5/issues/12802
// https://github.com/cypress-io/cypress/issues/26155
Cypress.Commands.add('ckType', {prevSubject: true}, (subject, content) => {
    const editor = subject[0].ckeditorInstance;
    editor.setData(content);
    return subject;
})

// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })
