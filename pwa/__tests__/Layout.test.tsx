import { render, screen } from '@testing-library/react'
import Layout from '../components/common/Layout'

describe('Layout', () => {
  it('renders children correctly', () => {
    const testContent = 'Test Content'
    const mockDehydratedState = {
      mutations: [],
      queries: []
    }
    
    render(
      <Layout dehydratedState={mockDehydratedState}>
        <div>{testContent}</div>
      </Layout>
    )

    expect(screen.getByText(testContent)).toBeInTheDocument()
  })
})
