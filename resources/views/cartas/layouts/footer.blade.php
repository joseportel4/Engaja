<footer class="cpe-footer">
  <div class="cpe-footer__container">

    {{-- Logo --}}
    <div class="cpe-footer__top">
      <div class="cpe-footer__brand">
        <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar" class="cpe-footer__logo">
      </div>
    </div>

    <hr class="cpe-footer__divider">

    {{-- Realização e Parceria --}}
    <div class="cpe-footer__partners">
      <div class="cpe-footer__partner">
        <div class="cpe-footer__partner-label">Realização</div>
        <img src="{{ asset('images/ipf-white.png') }}" alt="Instituto Paulo Freire" class="cpe-footer__partner-img">
      </div>
      <div class="cpe-footer__partner">
        <div class="cpe-footer__partner-label">Parceria</div>
        <img src="{{ asset('images/petrobras-white.png') }}" alt="Petrobras" class="cpe-footer__partner-img">
      </div>
    </div>

    <hr class="cpe-footer__divider">

  </div>

  <div class="cpe-footer__legal">
    <small>INSTITUTO DE EDUCAÇÃO E DIREITOS HUMANOS PAULO FREIRE | CNPJ 04.950.603/0001-05</small>
  </div>
</footer>

<style>
  .cpe-footer {
    background: var(--cpe-purple, #a900d9);
    color: #fff;
    margin-top: auto;
    padding-top: 48px;
  }

  .cpe-footer__container {
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 24px;
  }

  .cpe-footer__top {
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding-bottom: 8px;
  }

  .cpe-footer__brand {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .cpe-footer__logo {
    height: 48px;
    width: auto;
    filter: brightness(0) invert(1);
  }

  .cpe-footer__divider {
    border: 0;
    border-top: 1px solid rgba(255, 255, 255, 0.25);
    margin: 24px 0;
  }

  .cpe-footer__partners {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 64px;
    flex-wrap: wrap;
    text-align: center;
    padding-bottom: 8px;
  }

  .cpe-footer__partner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  .cpe-footer__partner-label {
    font-size: 14px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.85);
  }

  .cpe-footer__partner-img {
    max-height: 42px;
    width: auto;
    object-fit: contain;
  }

  .cpe-footer__legal {
    background: rgba(0, 0, 0, 0.12);
    text-align: center;
    padding: 14px 24px;
    margin-top: 16px;
  }

  .cpe-footer__legal small {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 500;
    letter-spacing: 0.02em;
  }

  @media (max-width: 600px) {
    .cpe-footer__partners {
      gap: 36px;
    }
  }
</style>
