describe('Login', () => {
    it('shows the login page', () => {
        cy.visit('/login');
        cy.get('[name="email"]').type('grant.don@example.net');
        cy.get('[name="password"]').type('password');
        cy.get('button[type="submit"]').click();
        cy.url().should('include', '/two-factor-challenge');
    });

    it('displays an error for invalid credentials', () => {
        cy.visit('/login');
        cy.get('[name="email"]').type('foo@example.com');
        cy.get('[name="password"]').type('wrongpassword');
        cy.get('button[type="submit"]').click();
        cy.get('.flex.gap-6 > .gap-6 > :nth-child(1) > div > .text-sm').should('be.visible').and('contain', 'These credentials do not match our records.');

    });

    it('logs in a user', () =>{
        cy.createModel('User')
            .then(user => {
                cy.log('The created user is:', user);
            })
    })
});

