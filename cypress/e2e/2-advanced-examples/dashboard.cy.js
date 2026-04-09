describe('Dashboard', () => {
    it('should not allow guest to view the dashboard', () => {
        cy.visit('/dashboard')
            .url().should('include', '/login');
    })
})
