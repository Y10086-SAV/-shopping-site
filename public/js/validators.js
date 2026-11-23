/**
 * 表单校验：与服务端规则保持一致
 * 邮箱、手机号使用正则表达式进行格式校验
 */
window.ShopValidators = (function () {
  const patterns = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    phone: /^1\d{10}$/
  };

  function validateLogin(form) {
    const errors = {};
    if (!form.username || !form.username.trim()) errors.username = '请输入用户名';
    if (!form.password) errors.password = '请输入密码';
    return errors;
  }

  function validateRegister(form) {
    const errors = {};
    if (!form.username || form.username.trim().length < 2) {
      errors.username = '用户名至少 2 个字符';
    }
    if (!form.email) {
      errors.email = '请输入邮箱';
    } else if (!patterns.email.test(form.email)) {
      errors.email = '请输入有效邮箱';
    }
    if (!form.password) {
      errors.password = '请输入密码';
    } else if (form.password.length < 6) {
      errors.password = '密码至少 6 位';
    }
    if (!form.confirm) {
      errors.confirm = '请再次输入密码';
    } else if (form.password !== form.confirm) {
      errors.confirm = '两次密码不一致';
    }
    return errors;
  }

  function validateCheckout(form) {
    const errors = {};
    if (!form.receiver || !form.receiver.trim()) {
      errors.receiver = '请填写收货人';
    }
    if (!form.phone) {
      errors.phone = '请填写手机号';
    } else if (!patterns.phone.test(form.phone)) {
      errors.phone = '请填写有效的 11 位手机号';
    }
    if (!form.address || form.address.trim().length < 5) {
      errors.address = '请填写详细收货地址（至少 5 个字）';
    }
    return errors;
  }

  return { patterns, validateLogin, validateRegister, validateCheckout };
})();
