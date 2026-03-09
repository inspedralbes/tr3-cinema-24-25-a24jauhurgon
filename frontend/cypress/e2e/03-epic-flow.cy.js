describe('Epopeia de Compra - Flux Complet d\'Admin a Client', () => {

    // Configura el timeout global per aquest test pesat (L'entorn Docker pot ser lent)
    const BIG_TIMEOUT = 30000;

    beforeEach(() => {
        cy.clearLocalStorage()
        cy.visit('/')
    })

    it('Realitza el flux complet: Obrir venda (Admin) -> Compra (User) -> PDF', () => {

        // --- BLOC 1: ADMINISTRADOR ---
        cy.log('STEP 1: Entrant com a Administrador...')
        cy.get('input[type="email"]').type('admin@ultimahorabcn.cat')
        cy.get('input[type="password"]').type('password')
        cy.intercept('POST', '**/api/auth/login').as('adminLogin')
        cy.get('button[type="submit"]').click()
        cy.wait('@adminLogin').its('response.statusCode').should('eq', 200)

        cy.log('STEP 2: Obrint la venda del primer vol...')
        cy.intercept('GET', '**/api/admin/monitoritzacio').as('getMonitoritzacio')
        cy.visit('/admin')
        cy.wait('@getMonitoritzacio', { timeout: BIG_TIMEOUT })
        cy.contains('Dashboard', { timeout: BIG_TIMEOUT }).should('be.visible')

        // Esperar que el loader marxi i apareguin les dades
        cy.contains('Carregant dades', { timeout: BIG_TIMEOUT }).should('not.exist')

        // Busquem el primer vol que es pugui obrir de forma dinàmica
        cy.intercept('POST', '**/api/admin/vols-interns/*/force-status').as('forceStatusRequest')

        // Busquem una fila que tingui el botó "Obrir Venda"
        cy.contains('tr', 'Obrir Venda', { timeout: BIG_TIMEOUT }).first().within(() => {
            // Guardem el codi de destí (ex: AMS, LHR...) per usar-lo després (amb trim)
            cy.get('div.font-bold.text-lg').invoke('text').then(t => t.trim()).as('flightDest')
            cy.contains('Obrir Venda').click()
        })

        // VERIFICACIÓ OPTIMISTA: Hauria de canviar a l'ACTE (gràcies a l'optimisme del dashboard)
        cy.get('@flightDest').then(dest => {
            cy.contains('tr', dest).should('contain', 'OBERTA', { timeout: BIG_TIMEOUT })
        })

        // Esperem que la petició de forçar estat es completi al backend (que pot tragar més)
        cy.wait('@forceStatusRequest', { timeout: BIG_TIMEOUT })

        cy.log('STEP 3: Tancant sessió d\'administrador...')
        cy.contains('button', 'Sortir').click()
        cy.url({ timeout: BIG_TIMEOUT }).should('eq', Cypress.config().baseUrl + '/')


        // --- BLOC 2: USUARI GENERAL ---
        cy.log('STEP 4: Entrant com a Usuari General...')
        cy.get('input[type="email"]').type('general@example.com')
        cy.get('input[type="password"]').type('password')
        cy.intercept('POST', '**/api/auth/login').as('userLogin')
        cy.get('button[type="submit"]').click()
        cy.wait('@userLogin').its('response.statusCode').should('eq', 200)

        cy.url({ timeout: BIG_TIMEOUT }).should('include', '/vols')

        // Triem el mateix vol que hem obert a l'Admin
        cy.get('@flightDest').then(dest => {
            cy.log(`STEP 5: Reservant seient al vol de ${dest}...`)
            cy.contains(dest, { timeout: BIG_TIMEOUT }).parents('.rounded-xl').within(() => {
                cy.contains('Reservar Seient', { timeout: BIG_TIMEOUT }).click()
            })
        })

        cy.log('STEP 6: Gestionant la cua...')
        // Esperem a que aparegui el botó d'entrada o directament el mapa de seients
        cy.get('body', { timeout: BIG_TIMEOUT }).then($body => {
            if ($body.find('button:contains("Entrar a la Cua")').length > 0) {
                cy.contains('Entrar a la Cua').click()
            }
        })

        // Esperem arribar al mapa de seients (redirecció des de /cua o /esperant)
        cy.url({ timeout: BIG_TIMEOUT }).should('include', '/seients')

        cy.log('STEP 7: Seleccionant seient i confirmant...')
        cy.get('.bg-primary.cursor-pointer', { timeout: BIG_TIMEOUT }).first().click({ force: true })
        cy.contains('Confirmar Seient').click()

        cy.log('STEP 8: Check-out i Pagament...')
        cy.url({ timeout: BIG_TIMEOUT }).should('include', '/resum')

        // Esperar que la llista de passatgers s'inicialitzi
        cy.get('input[placeholder="Passatger 1"]', { timeout: BIG_TIMEOUT }).should('be.visible')

        // Emplenar dades dummy (Només nom i correu en aquesta versió)
        cy.get('input[placeholder="Passatger 1"]').clear().type('Cypress User')
        cy.get('input[type="email"]').clear({ force: true }).type('general@example.com', { force: true })
        cy.get('#condicions').check({ force: true })

        cy.intercept('POST', '**/api/compra/*/confirmar').as('paga')
        cy.get('button').contains('Confirmar i Pagar').click()
        cy.wait('@paga').its('response.statusCode').should('be.oneOf', [200, 201])

        cy.log('STEP 9: Verificació de compra i PDF...')
        cy.url({ timeout: BIG_TIMEOUT }).should('include', '/completada')
        cy.contains('Reserva Confirmada!').should('be.visible')

        // Verifiquem que el botó de PDF existeix i el cliquem
        cy.contains('Descarregar Bitllet (PDF)').should('be.visible').click()

        cy.log('--- TEST FINALITZAT AMB ÈXIT ---')
    })
})
