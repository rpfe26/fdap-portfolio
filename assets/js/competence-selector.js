/**
 * FDAP Competence Selector
 * Gestion interactive du référentiel CAP EPC
 */

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('fdap-competence-selector');
    if (!container) return;

    const textarea = document.getElementById('fdap_competences');
    const domaineInput = document.getElementById('fdap_domaine');
    const referentiel = window.fdapReferentiel || [];

    let state = {
        selectedDomaine: domaineInput.value || '',
        competences: textarea.value ? textarea.value.split('\n').map(c => c.trim()).filter(c => c) : [],
        expandedMeta: null
    };

    function render() {
        const selectedPole = referentiel.find(p => p.label === state.selectedDomaine);

        let html = `
            <div class="fdap-selector-wrap">
                <label class="fdap-label-step">Étape 1 : Choisissez un pôle d'activité</label>
                <div class="fdap-poles-grid">
                    ${referentiel.map(pole => `
                        <button type="button" class="fdap-pole-btn ${state.selectedDomaine === pole.label ? 'active' : ''}" data-label="${pole.label}">
                            <div class="pole-icon">${pole.id === 'P1' ? '📦' : pole.id === 'P2' ? '🏪' : '💁‍♂️'}</div>
                            <div class="pole-id">${pole.id}</div>
                            <div class="pole-label">${pole.label.split(':')[1]?.trim()}</div>
                        </button>
                    `).join('')}
                </div>

                ${selectedPole ? `
                    <div class="fdap-metas-wrap anim-fade-in">
                        <label class="fdap-label-step" style="margin-top:24px">Étape 2 : Détaillez vos compétences</label>
                        <div class="fdap-metas-list">
                            ${selectedPole.metaCompetences.map(meta => {
                                const isOpen = state.expandedMeta === meta.id;
                                const isMetaSelected = state.competences.includes(`[Méta] ${meta.label}`);
                                
                                return `
                                    <div class="fdap-meta-item ${isOpen ? 'is-open' : ''}">
                                        <div class="fdap-meta-header" data-id="${meta.id}">
                                            <div class="fdap-meta-title">
                                                <input type="checkbox" class="fdap-meta-cb" ${isMetaSelected ? 'checked' : ''} data-label="[Méta] ${meta.label}" data-subs='${JSON.stringify(meta.subCompetences)}'>
                                                <span>${meta.label}</span>
                                            </div>
                                            <span class="fdap-chevron">${isOpen ? '▲' : '▼'}</span>
                                        </div>
                                        <div class="fdap-subs-list" style="display: ${isOpen ? 'block' : 'none'}">
                                            ${meta.subCompetences.map(sub => `
                                                <label class="fdap-sub-label ${state.competences.includes(sub) ? 'active' : ''}">
                                                    <input type="checkbox" class="fdap-sub-cb" ${state.competences.includes(sub) ? 'checked' : ''} data-label="${sub}">
                                                    <span>${sub}</span>
                                                </label>
                                            `).join('')}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                ` : `
                    <div class="fdap-empty-state">
                        Veuillez sélectionner un domaine pour afficher les compétences.
                    </div>
                `}

                <div class="fdap-summary-wrap" style="margin-top:24px">
                    <label class="fdap-label-step" style="font-size:0.7rem; color:#94a3b8">📋 Résumé de votre sélection</label>
                    <div class="fdap-summary-content">
                        ${state.competences.length > 0 
                            ? state.competences.map(c => {
                                const isMeta = c.startsWith('[Méta]');
                                return `<div class="summary-line ${isMeta ? 'is-meta' : ''}">
                                    <span>${isMeta ? '📌' : '•'}</span>
                                    <span>${isMeta ? c.replace('[Méta] ', '') : c}</span>
                                </div>`;
                            }).join('')
                            : '<span class="empty">Aucune compétence sélectionnée.</span>'
                        }
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = html;
        bindEvents();
    }

    function bindEvents() {
        // Clic sur un pôle
        container.querySelectorAll('.fdap-pole-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                state.selectedDomaine = btn.dataset.label;
                domaineInput.value = state.selectedDomaine;
                state.expandedMeta = null;
                render();
            });
        });

        // Toggle accordéon
        container.querySelectorAll('.fdap-meta-header').forEach(header => {
            header.addEventListener('click', (e) => {
                if (e.target.type === 'checkbox') return;
                const id = header.dataset.id;
                state.expandedMeta = state.expandedMeta === id ? null : id;
                render();
            });
        });

        // Checkbox Méta
        container.querySelectorAll('.fdap-meta-cb').forEach(cb => {
            cb.addEventListener('change', () => {
                const label = cb.dataset.label;
                const subs = JSON.parse(cb.dataset.subs);
                
                if (cb.checked) {
                    if (!state.competences.includes(label)) state.competences.push(label);
                    subs.forEach(s => {
                        if (!state.competences.includes(s)) state.competences.push(s);
                    });
                } else {
                    state.competences = state.competences.filter(c => c !== label && !subs.includes(c));
                }
                updateFinalValue();
                render();
            });
        });

        // Checkbox Sous-compétence
        container.querySelectorAll('.fdap-sub-cb').forEach(cb => {
            cb.addEventListener('change', () => {
                const label = cb.dataset.label;
                if (cb.checked) {
                    if (!state.competences.includes(label)) state.competences.push(label);
                } else {
                    state.competences = state.competences.filter(c => c !== label);
                }
                updateFinalValue();
                render();
            });
        });
    }

    function updateFinalValue() {
        // Ordonner les compétences selon le référentiel
        const ordered = [];
        referentiel.forEach(pole => {
            pole.metaCompetences.forEach(meta => {
                const metaLabel = `[Méta] ${meta.label}`;
                if (state.competences.includes(metaLabel)) ordered.push(metaLabel);
                meta.subCompetences.forEach(sub => {
                    if (state.competences.includes(sub)) ordered.push(sub);
                });
            });
        });

        // Garder les éventuels labels personnalisés
        const extra = state.competences.filter(c => !ordered.includes(c));
        const final = [...ordered, ...extra];
        
        textarea.value = final.join('\n');
    }

    render();
});
