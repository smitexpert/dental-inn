import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import PageHeaderSticky from '@/Components/ui/PageHeaderSticky.vue'
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue'

const breadcrumb = [
  { label: 'Admin', href: '/admin' },
  { label: 'Users' },
]

describe('PageHeaderSticky', () => {
  it('renders H1 from title prop', () => {
    const w = mount(PageHeaderSticky, { props: { title: 'Team members', breadcrumb } })
    expect(w.find('h1').text()).toBe('Team members')
  })

  it('renders breadcrumbs component', () => {
    const w = mount(PageHeaderSticky, { props: { title: 'X', breadcrumb } })
    expect(w.findComponent(Breadcrumbs).exists()).toBe(true)
  })

  it('applies sticky-page-header class', () => {
    const w = mount(PageHeaderSticky, { props: { title: 'X', breadcrumb } })
    expect(w.classes().join(' ')).toMatch(/sticky-page-header/)
  })

  it('renders the actions slot', () => {
    const w = mount(PageHeaderSticky, {
      props: { title: 'X', breadcrumb },
      slots: { actions: '<button>New</button>' },
    })
    expect(w.find('button').text()).toBe('New')
  })
})
