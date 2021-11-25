import * as core from '@actions/core'
import issueParser from 'issue-parser'

async function run(): Promise<void> {
  try {
    const issueBody: string = core.getInput('body')

    const overrides: string = core.getInput('overrides')

    const parse = issueParser('github', JSON.parse(overrides))

    const output = parse(issueBody)

    core.info(`Parsed issue body: ${JSON.stringify(output)}`)

    core.setOutput('parsed', JSON.parse(overrides))
    core.setOutput('parsed', output)
  } catch (error) {
    core.setFailed(error.message)
  }
}

run()
