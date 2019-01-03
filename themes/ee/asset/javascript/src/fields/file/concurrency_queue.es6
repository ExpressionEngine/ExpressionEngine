/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class ConcurrencyQueue {

  constructor({concurrency}) {
    this.concurrency = concurrency
    this.queue = []
    this.currentlyRunning = 0
  }

  enqueue(items, factory) {
    items.forEach(item => {
      this.queue.push({item: item, factory: factory})
    })
    this.start()
  }

  start() {
    while (this.currentlyRunning < this.concurrency && this.queue.length > 0) {
      let {item, factory} = this.queue.shift()
      this.currentlyRunning++

      factory(item).then(() => {
        this.currentlyRunning--
        this.start()
      })
      .catch(() => {
        this.currentlyRunning--
        this.start()
      })
    }
  }
}
